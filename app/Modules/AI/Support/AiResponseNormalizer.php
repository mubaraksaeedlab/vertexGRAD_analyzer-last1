<?php

namespace App\Modules\AI\Support;

class AiResponseNormalizer
{
    public function normalizeProjectInsight(array $response): array
    {
        return [
            'model_name' => $response['model_name'] ?? 'internal-engine',
            'model_version' => $response['model_version'] ?? 'v1',
            'status' => $response['status'] ?? 'completed',
            'maturity_level' => $response['maturity_level'] ?? 'intermediate',
            'overall_health' => $response['overall_health'] ?? 'moderate',
            'readiness_score' => isset($response['readiness_score']) ? (int) $response['readiness_score'] : 50,
            'risk_level' => $response['risk_level'] ?? 'medium',
            'confidence_level' => $response['confidence_level'] ?? 'medium',
            'summary' => $response['summary'] ?? null,
            'architecture_review' => $response['architecture_review'] ?? null,
            'risk_assessment' => $response['risk_assessment'] ?? null,
            'decision_support' => $response['decision_support'] ?? null,
            'strengths' => $this->normalizeArray($response['strengths'] ?? []),
            'weaknesses' => $this->normalizeArray($response['weaknesses'] ?? []),
            'top_risks' => $this->normalizeArray($response['top_risks'] ?? []),
            'recommendations' => $this->normalizeArray($response['recommendations'] ?? []),
            'metadata' => is_array($response['metadata'] ?? null) ? $response['metadata'] : [],
        ];
    }

    public function normalizeIssueInsight(array $response): array
    {
        return [
            'title' => $response['title'] ?? null,
            'explanation' => $response['explanation'] ?? null,
            'impact' => $response['impact'] ?? null,
            'root_cause' => $response['root_cause'] ?? null,
            'fix_suggestion' => $response['fix_suggestion'] ?? null,
            'priority_note' => $response['priority_note'] ?? null,
            'confidence_score' => $response['confidence_score'] ?? 80,
            'evidence' => is_array($response['evidence'] ?? null) ? $response['evidence'] : [],
            'metadata' => is_array($response['metadata'] ?? null) ? $response['metadata'] : [],
        ];
    }

    protected function normalizeArray(mixed $value): array
    {
        return is_array($value) ? array_values($value) : [];
    }
}