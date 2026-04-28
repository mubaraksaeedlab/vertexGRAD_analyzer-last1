<?php

namespace App\Modules\AI\Services;

use App\Modules\AI\Models\AiInsight;
use App\Modules\AI\Prompts\ProjectSummaryPromptBuilder;
use App\Modules\Analysis\Models\AnalysisRun;

class AiInsightOrchestrator
{
    public function __construct(
        protected AiContextBuilderService $contextBuilder,
        protected ProjectSummaryPromptBuilder $projectSummaryPromptBuilder,
        protected AiGatewayService $gatewayService,
    ) {
    }

    public function generateForAnalysisRun(AnalysisRun $analysisRun): AiInsight
    {
        $context = $this->contextBuilder->buildForAnalysisRun($analysisRun);
        $promptPayload = $this->projectSummaryPromptBuilder->build($context);
        $result = $this->gatewayService->generateProjectInsight($promptPayload);

        return AiInsight::updateOrCreate(
            [
                'project_id' => $analysisRun->project_id,
                'analysis_run_id' => $analysisRun->id,
            ],
            [
                'model_name' => $result['model_name'] ?? null,
                'model_version' => $result['model_version'] ?? null,
                'status' => $result['status'] ?? 'completed',
                'maturity_level' => $result['maturity_level'] ?? null,
                'overall_health' => $result['overall_health'] ?? null,
                'readiness_score' => $result['readiness_score'] ?? null,
                'risk_level' => $result['risk_level'] ?? null,
                'confidence_level' => $result['confidence_level'] ?? null,
                'summary' => $result['summary'] ?? null,
                'architecture_review' => $result['architecture_review'] ?? null,
                'risk_assessment' => $result['risk_assessment'] ?? null,
                'decision_support' => $result['decision_support'] ?? null,
                'strengths' => $result['strengths'] ?? null,
                'weaknesses' => $result['weaknesses'] ?? null,
                'top_risks' => $result['top_risks'] ?? null,
                'recommendations' => $result['recommendations'] ?? null,
                'metadata' => $result['metadata'] ?? null,
                'generated_at' => now(),
            ]
        );
    }
}