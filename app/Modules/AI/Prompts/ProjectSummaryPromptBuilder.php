<?php

namespace App\Modules\AI\Prompts;

class ProjectSummaryPromptBuilder
{
    public function build(array $context): array
    {
        $projectName = data_get($context, 'project.name', 'Unknown Project');
        $primaryLanguage = data_get($context, 'project.primary_language', 'unknown');
        $detectedLanguages = data_get($context, 'project.detected_languages', []);
        $issueTotal = data_get($context, 'issue_statistics.total', 0);
        $severityStats = data_get($context, 'issue_statistics.by_severity', []);
        $categoryStats = data_get($context, 'issue_statistics.by_category', []);
        $overallScore = data_get($context, 'score.overall_score');
        $grade = data_get($context, 'score.grade');
        $topIssues = data_get($context, 'top_issues', []);
        $riskProfile = data_get($context, 'risk_profile', []);
        $qualitySignals = data_get($context, 'quality_signals', []);

        $systemPrompt = <<<TEXT
You are a senior software quality reviewer, architecture evaluator, and technical assessment assistant.

Your task is to generate a professional project-level AI assessment based strictly on the provided static analysis context.

Requirements:
- Be structured, concise, and technically meaningful.
- Focus on project health, issue severity, maintainability, quality, and readiness.
- Use only the supplied context.
- Do not invent unsupported facts.
- Highlight meaningful risks and strengths.
- Write in a professional tone suitable for dashboards and review panels.
TEXT;

        $userPrompt = [
            'project_name' => $projectName,
            'primary_language' => $primaryLanguage,
            'detected_languages' => $detectedLanguages,
            'issue_total' => $issueTotal,
            'severity_statistics' => $severityStats,
            'category_statistics' => $categoryStats,
            'overall_score' => $overallScore,
            'grade' => $grade,
            'top_issues' => $topIssues,
            'risk_profile' => $riskProfile,
            'quality_signals' => $qualitySignals,
            'project_context' => data_get($context, 'project', []),
            'analysis_context' => data_get($context, 'analysis_run', []),
        ];

        return [
            'system_prompt' => $systemPrompt,
            'user_prompt' => $userPrompt,
        ];
    }
}