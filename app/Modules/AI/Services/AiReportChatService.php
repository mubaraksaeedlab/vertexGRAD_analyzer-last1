<?php

namespace App\Modules\AI\Services;

use App\Modules\Reports\Models\Report;

class AiReportChatService
{
    public function __construct(
        protected AiGatewayService $gateway
    ) {
    }

    public function ask(Report $report, string $question): string
    {
        $data = is_array($report->report_data)
            ? $report->report_data
            : (json_decode($report->report_data ?? "[]", true) ?: []);

        $project = is_array($data["project"] ?? null) ? $data["project"] : [];
        $analysisRun = is_array($data["analysis_run"] ?? null) ? $data["analysis_run"] : [];
        $score = is_array($data["score"] ?? null) ? $data["score"] : [];
        $issues = is_array($data["issues_preview"] ?? null) ? $data["issues_preview"] : [];
        $aiExecutiveInsight = (string) ($data["ai_executive_insight"] ?? "");

        $projectName = $project["name"] ?? "Unknown Project";
        $primaryLanguage = $project["primary_language"] ?? "unknown";
        $issuesFound = (int) ($analysisRun["issues_found"] ?? count($issues));
        $overallScore = $score["overall_score"] ?? "N/A";
        $grade = $score["grade"] ?? "N/A";

        $criticalCount = (int) ($score["critical_count"] ?? 0);
        $highCount = (int) ($score["high_count"] ?? 0);
        $mediumCount = (int) ($score["medium_count"] ?? 0);

        $riskLevel = $criticalCount > 0
            ? "CRITICAL"
            : ($highCount > 0
                ? "HIGH"
                : ($mediumCount > 0 ? "MEDIUM" : "LOW"));

        $topFindingsText = "No findings available.";

        if (!empty($issues)) {
            $lines = [];

            foreach (array_slice($issues, 0, 5) as $index => $issue) {
                $title = $issue["title"] ?? "Untitled issue";
                $severity = $issue["severity"] ?? "unknown";
                $category = $issue["category"] ?? "general";
                $recommendation = $issue["recommendation"] ?? "No recommendation provided.";

                $lines[] =
                    ($index + 1) . ". Title: {$title}\n" .
                    "   Severity: {$severity}\n" .
                    "   Category: {$category}\n" .
                    "   Recommendation: {$recommendation}";
            }

            $topFindingsText = implode("\n", $lines);
        }

        $prompt = "
You are an AI assistant inside the VertexGrad Analyzer Platform.

You are helping a student understand their own software analysis report.

Rules:
- Answer only based on the provided report.
- Be clear, practical, and student-friendly.
- If the student asks what to fix first, prioritize by severity and security impact.
- If the student asks in Arabic, answer in Arabic.
- Do not invent findings that are not present in the report.
- Keep the answer focused and helpful.

Project name: {$projectName}
Primary language: {$primaryLanguage}
Risk level: {$riskLevel}
Overall score: {$overallScore}
Grade: {$grade}
Issues found: {$issuesFound}

Top findings:
{$topFindingsText}

Executive insight:
{$aiExecutiveInsight}

Student question:
{$question}
";

        return $this->gateway->generateText(trim($prompt));
    }
}