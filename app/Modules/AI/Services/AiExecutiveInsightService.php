<?php

namespace App\Modules\AI\Services;

class AiExecutiveInsightService
{
    public function __construct(
        protected AiGatewayService $gateway
    ) {
    }

    public function generate(array $reportData): string
    {
        $projectName = $reportData["project_name"] ?? "Unknown Project";
        $riskLevel = $reportData["risk_level"] ?? "UNKNOWN";
        $overallScore = $reportData["overall_score"] ?? "N/A";
        $grade = $reportData["grade"] ?? "N/A";
        $issuesFound = $reportData["issues_found"] ?? 0;
        $primaryLanguage = $reportData["primary_language"] ?? "unknown";
        $projectReadiness = $reportData["project_readiness"] ?? "N/A";
        $systemDecision = $reportData["system_decision"] ?? "Unknown";
        $topFindings = $reportData["top_findings"] ?? [];

        $findingsText = "No major findings provided.";

        if (!empty($topFindings)) {
            $lines = [];
            foreach ($topFindings as $index => $finding) {
                $title = $finding["title"] ?? "Untitled finding";
                $severity = $finding["severity"] ?? "Unknown";
                $lines[] = ($index + 1) . ". {$title} - {$severity}";
            }
            $findingsText = implode("\n", $lines);
        }

        $prompt = "
You are an AI assistant for the VertexGrad Analyzer Platform.

Analyze this software project report and write:
1. A short executive summary
2. The top 3 priorities
3. A readiness assessment

Project name: {$projectName}
Risk level: {$riskLevel}
Overall score: {$overallScore}
Grade: {$grade}
Issues found: {$issuesFound}
Primary language: {$primaryLanguage}
Project readiness: {$projectReadiness}
System decision: {$systemDecision}

Top findings:
{$findingsText}

Keep the answer concise, professional, and suitable for a graduation project platform.
";

        return $this->gateway->generateText($prompt);
    }
}