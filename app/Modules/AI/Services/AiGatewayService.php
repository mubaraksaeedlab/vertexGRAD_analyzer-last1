<?php

namespace App\Modules\AI\Services;

use App\Modules\AI\Support\AiResponseNormalizer;
use Illuminate\Support\Facades\Http;

class AiGatewayService
{
    public function __construct(
        protected AiResponseNormalizer $normalizer
    ) {
    }

    public function generateText(string $prompt): string
    {
        $provider = config("ai.provider");

        return match ($provider) {
            "gemini" => $this->sendToGemini($prompt),
            "openrouter" => $this->sendToOpenRouter($prompt),
            default => throw new \RuntimeException("Unsupported AI provider: {$provider}"),
        };
    }

    protected function sendToGemini(string $prompt): string
    {
        $apiKey = config("ai.api_key");
        $model = config("ai.model", "gemini-2.5-flash");
        $timeout = config("ai.timeout", 120);

        if (empty($apiKey)) {
            throw new \RuntimeException("Gemini API key is missing.");
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = Http::timeout($timeout)
            ->acceptJson()
            ->post($url, [
                "contents" => [
                    [
                        "parts" => [
                            [
                                "text" => $prompt,
                            ],
                        ],
                    ],
                ],
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException("Gemini request failed: " . $response->body());
        }

        $text = data_get($response->json(), "candidates.0.content.parts.0.text");

        if (!$text) {
            throw new \RuntimeException("Gemini response did not contain text.");
        }

        return trim($text);
    }

    protected function sendToOpenRouter(string $prompt): string
    {
        $apiKey = config("ai.api_key");
        $model = config("ai.model", "openrouter/free");
        $timeout = config("ai.timeout", 120);

        if (empty($apiKey)) {
            throw new \RuntimeException("OpenRouter API key is missing.");
        }

        $response = Http::timeout($timeout)
            ->acceptJson()
            ->withHeaders([
                "Authorization" => "Bearer {$apiKey}",
                "Content-Type" => "application/json",
                "HTTP-Referer" => config("app.url"),
                "X-Title" => config("app.name", "VertexGrad Analyzer"),
            ])
            ->post("https://openrouter.ai/api/v1/chat/completions", [
                "model" => $model,
                "messages" => [
                    [
                        "role" => "user",
                        "content" => $prompt,
                    ],
                ],
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException("OpenRouter request failed: " . $response->body());
        }

        $text = data_get($response->json(), "choices.0.message.content");

        if (!$text) {
            throw new \RuntimeException("OpenRouter response did not contain text.");
        }

        return trim($text);
    }

    public function generateProjectInsight(array $promptPayload): array
    {
        $context = $promptPayload["user_prompt"] ?? [];

        $projectName = data_get($context, "project_name", "Unknown Project");
        $primaryLanguage = data_get($context, "primary_language", "unknown");
        $issueCount = (int) data_get($context, "issue_total", 0);
        $severityStats = data_get($context, "severity_statistics", []);
        $topIssues = data_get($context, "top_issues", []);
        $riskProfile = data_get($context, "risk_profile", []);
        $qualitySignals = data_get($context, "quality_signals", []);
        $categoryStats = data_get($context, "category_statistics", []);

        $critical = (int) ($severityStats["critical"] ?? 0);
        $high = (int) ($severityStats["high"] ?? 0);
        $medium = (int) ($severityStats["medium"] ?? 0);
        $low = (int) ($severityStats["low"] ?? 0);

        $securityCount = (int) ($categoryStats["security"] ?? 0);
        $performanceCount = (int) ($categoryStats["performance"] ?? 0);
        $structureCount = (int) ($categoryStats["structure"] ?? 0);

        $hasCritical = (bool) ($riskProfile["has_critical"] ?? false);
        $highRatio = (float) ($riskProfile["high_ratio"] ?? 0);
        $issueDensity = (int) ($qualitySignals["issue_density"] ?? 0);
        $confidenceAvg = (float) ($qualitySignals["confidence_avg"] ?? 0);

        $mainIssue = $topIssues[0]["title"] ?? null;
        $mainIssueCategory = $topIssues[0]["category"] ?? "quality";
        $mainIssueSeverity = strtolower((string) ($topIssues[0]["severity"] ?? "low"));

        $maturityLevel = $issueCount <= 2 ? "advanced" : ($issueCount <= 6 ? "intermediate" : "basic");

        $overallHealth = $hasCritical
            ? "critical"
            : (($high > 0 || $highRatio >= 0.5)
                ? "weak"
                : ($issueCount <= 2 ? "good" : ($issueCount <= 6 ? "moderate" : "weak")));

        $riskLevel = $hasCritical
            ? "critical"
            : ($high > 0
                ? "high"
                : ($medium > 0 ? "medium" : "low"));

        $readinessScore = 100;
        $readinessScore -= ($critical * 35);
        $readinessScore -= ($high * 20);
        $readinessScore -= ($medium * 8);
        $readinessScore -= ($low * 2);

        if ($securityCount > 0) {
            $readinessScore -= min(20, $securityCount * 10);
        }

        if ($performanceCount > 2) {
            $readinessScore -= 5;
        }

        if ($structureCount > 2) {
            $readinessScore -= 5;
        }

        if ($highRatio >= 0.5) {
            $readinessScore -= 10;
        }

        if ($mainIssueCategory === "security" && in_array($mainIssueSeverity, ["critical", "high"], true)) {
            $readinessScore -= 15;
        }

        if ($issueDensity >= 10) {
            $readinessScore -= 10;
        } elseif ($issueDensity >= 5) {
            $readinessScore -= 5;
        }

        if ($issueCount <= 2 && $confidenceAvg >= 80 && $high === 0 && $critical === 0) {
            $readinessScore += 3;
        }

        $readinessScore = max(0, min(100, $readinessScore));

        $confidenceLevel = $confidenceAvg >= 85
            ? "high"
            : ($confidenceAvg >= 60 ? "medium" : "low");

        $summary = $mainIssue
            ? "Project {$projectName} shows a key issue: {$mainIssue}, indicating potential {$mainIssueCategory} risks."
            : "Project {$projectName} has {$issueCount} issue(s) detected.";

        $architectureReview = $hasCritical
            ? "Critical issues indicate architectural or security weaknesses that require immediate attention."
            : (($high > 0 || $highRatio >= 0.5)
                ? "High-severity issue concentration suggests that the project needs focused remediation in key risk areas."
                : "The project structure appears stable with manageable issue distribution.");

        $decision = $hasCritical
            ? "Project is not ready and requires urgent fixes before any approval."
            : ($high > 0
                ? "Project is not yet ready for advancement until high-severity issues are resolved."
                : ($issueCount <= 2
                    ? "Project appears ready for next stage."
                    : "Project requires improvements before approval."));

        $strengths = [
            "Analysis pipeline is structured and reliable.",
        ];

        if ($confidenceAvg >= 80) {
            $strengths[] = "Detected issues have strong confidence signals, improving review reliability.";
        }

        $weaknesses = [];

        if ($hasCritical) {
            $weaknesses[] = "Presence of critical issues.";
        }

        if ($high > 0) {
            $weaknesses[] = "Presence of unresolved high-severity issues.";
        }

        if ($securityCount > 0) {
            $weaknesses[] = "Security findings reduce deployment readiness.";
        }

        if ($issueDensity > 5) {
            $weaknesses[] = "Issue density suggests broader quality concerns across the codebase.";
        }

        if (empty($weaknesses)) {
            $weaknesses[] = "Minor issues present.";
        }

        $response = [
            "model_name" => "internal-smart-engine",
            "model_version" => "v7",
            "status" => "completed",
            "maturity_level" => $maturityLevel,
            "overall_health" => $overallHealth,
            "readiness_score" => $readinessScore,
            "risk_level" => $riskLevel,
            "confidence_level" => $confidenceLevel,
            "summary" => $summary,
            "architecture_review" => $architectureReview,
            "risk_assessment" => "Critical: {$critical}, High: {$high}, Medium: {$medium}, Low: {$low}.",
            "decision_support" => $decision,
            "strengths" => $strengths,
            "weaknesses" => $weaknesses,
            "top_risks" => $topIssues,
            "recommendations" => [
                [
                    "priority" => "high",
                    "title" => "Fix top issues first",
                    "action" => "Address the highest severity issues identified in the analysis before progressing.",
                ],
                [
                    "priority" => "medium",
                    "title" => "Review code health indicators",
                    "action" => "Inspect issue density and average confidence to prioritize broader remediation work.",
                ],
            ],
            "metadata" => [
                "source" => "smart-context-engine",
                "generated_from_prompt_payload" => true,
            ],
        ];

        return $this->normalizer->normalizeProjectInsight($response);
    }

    public function generateIssueInsight(array $promptPayload): array
    {
        $context = $promptPayload["user_prompt"] ?? [];

        $title = data_get($context, "title", "Detected issue");
        $category = data_get($context, "category", "quality");
        $severity = strtolower((string) data_get($context, "severity", "medium"));
        $ruleCode = data_get($context, "rule_code", "UNKNOWN_RULE");
        $recommendation = data_get($context, "recommendation", "Follow best practices to resolve this issue.");
        $confidence = (float) data_get($context, "confidence", 80);
        $language = data_get($context, "language", "code");
        $snippet = data_get($context, "snippet");

        $impact = match ($severity) {
            "critical" => "This issue represents a critical risk and may directly affect system security, integrity, or safe operation.",
            "high" => "This issue can lead to serious problems including security risks, instability, or significant technical debt.",
            "medium" => "This issue may negatively affect maintainability, readability, or long-term code quality.",
            default => "This issue has limited direct impact but should still be addressed as part of quality improvement.",
        };

        $rootCause = match ($category) {
            "security" => "This issue is likely associated with unsafe {$language} coding practices and indicates a deviation from expected security controls under rule '{$ruleCode}'.",
            "performance" => "This issue is likely related to inefficient {$language} implementation patterns associated with rule '{$ruleCode}'.",
            "structure" => "This issue is likely caused by structural or architectural deviations identified by rule '{$ruleCode}'.",
            default => "This issue is likely associated with the rule '{$ruleCode}' and indicates a deviation from expected coding or quality practices.",
        };

        $explanation = "The issue '{$title}' was detected under the {$category} category.";
        if (!empty($snippet)) {
            $explanation .= " The analyzer also captured a code snippet related to this finding, which increases the contextual relevance of the result.";
        }

        $response = [
            "title" => $title,
            "explanation" => $explanation,
            "impact" => $impact,
            "root_cause" => $rootCause,
            "fix_suggestion" => $recommendation,
            "priority_note" => strtoupper($severity) . " priority issue.",
            "confidence_score" => $confidence,
            "evidence" => [
                "rule_code" => $ruleCode,
                "language" => $language,
                "has_snippet" => !empty($snippet),
            ],
            "metadata" => [
                "source" => "smart-context-engine",
                "generated_from_prompt_payload" => true,
            ],
        ];

        return $this->normalizer->normalizeIssueInsight($response);
    }
}