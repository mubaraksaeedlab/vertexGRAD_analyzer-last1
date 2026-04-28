<?php

namespace App\Modules\AI\Services;

use App\Modules\AI\Models\AiIssueInsight;
use App\Modules\AI\Prompts\IssueInsightPromptBuilder;
use App\Modules\Analysis\Models\Issue;

class AiIssueInsightService
{
    public function __construct(
        protected IssueInsightPromptBuilder $issueInsightPromptBuilder,
        protected AiGatewayService $gatewayService,
    ) {
    }

    public function generateForIssue(Issue $issue): AiIssueInsight
    {
        $promptPayload = $this->issueInsightPromptBuilder->build($issue);
        $result = $this->gatewayService->generateIssueInsight($promptPayload);

        return AiIssueInsight::updateOrCreate(
            [
                'issue_id' => $issue->id,
            ],
            [
                'analysis_run_id' => $issue->analysis_run_id,
                'title' => $result['title'] ?? $issue->title,
                'explanation' => $result['explanation'] ?? null,
                'impact' => $result['impact'] ?? null,
                'root_cause' => $result['root_cause'] ?? null,
                'fix_suggestion' => $result['fix_suggestion'] ?? $issue->recommendation,
                'priority_note' => $result['priority_note'] ?? null,
                'confidence_score' => $result['confidence_score'] ?? $issue->confidence ?? 80,
                'evidence' => $result['evidence'] ?? [],
                'metadata' => $result['metadata'] ?? [],
            ]
        );
    }
}