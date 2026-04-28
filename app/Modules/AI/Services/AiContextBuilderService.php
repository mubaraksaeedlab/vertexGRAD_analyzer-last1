<?php

namespace App\Modules\AI\Services;

use App\Modules\Analysis\Models\AnalysisRun;

class AiContextBuilderService
{
    public function buildForAnalysisRun(AnalysisRun $analysisRun): array
    {
        $analysisRun->loadMissing([
            'project',
            'issues',
            'score',
            'reports',
        ]);

        $project = $analysisRun->project;
        $issues = $analysisRun->issues;
        $score = $analysisRun->score;

        $topIssues = $issues
            ->sortByDesc(function ($issue) {
                $severityScore = match ($issue->severity) {
                    'critical' => 4,
                    'high' => 3,
                    'medium' => 2,
                    default => 1,
                };

                return ($severityScore * 100) + (float) $issue->confidence;
            })
            ->take(5)
            ->values();

        return [
            'project' => [
                'id' => $project?->id,
                'name' => $project?->name,
                'primary_language' => $project?->primary_language,
                'detected_languages' => $project?->detected_languages ?? [],
                'status' => $project?->status,
                'scan_status' => $project?->scan_status,
                'total_files' => $project?->total_files,
                'source_files' => $project?->source_files,
                'total_lines' => $project?->total_lines,
                'summary' => $project?->summary,
                'metadata' => $project?->metadata,
            ],

            'analysis_run' => [
                'id' => $analysisRun->id,
                'run_uuid' => $analysisRun->run_uuid,
                'status' => $analysisRun->status,
                'stage' => $analysisRun->stage,
                'issues_found' => $analysisRun->issues_found,
                'duration_ms' => $analysisRun->duration_ms,
                'summary' => $analysisRun->summary,
                'metrics' => $analysisRun->metrics,
                'context' => $analysisRun->context,
                'started_at' => $analysisRun->started_at?->toDateTimeString(),
                'finished_at' => $analysisRun->finished_at?->toDateTimeString(),
            ],

            'score' => $score ? [
                'id' => $score->id,
                'overall_score' => $score->overall_score ?? null,
                'structure_score' => $score->structure_score ?? null,
                'quality_score' => $score->quality_score ?? null,
                'security_score' => $score->security_score ?? null,
                'performance_score' => $score->performance_score ?? null,
                'grade' => $score->grade ?? null,
                'summary' => $score->summary ?? null,
                'metadata' => $score->metadata ?? null,
            ] : null,

            'issues' => $issues->map(function ($issue) {
                return [
                    'id' => $issue->id,
                    'rule_code' => $issue->rule_code,
                    'category' => $issue->category,
                    'severity' => $issue->severity,
                    'language' => $issue->language,
                    'title' => $issue->title,
                    'description' => $issue->description,
                    'recommendation' => $issue->recommendation,
                    'confidence' => $issue->confidence,
                ];
            })->values()->all(),

            'top_issues' => $topIssues->map(function ($issue) {
                return [
                    'title' => $issue->title,
                    'severity' => $issue->severity,
                    'confidence' => $issue->confidence,
                    'category' => $issue->category,
                ];
            })->values()->all(),

            'issue_statistics' => [
                'total' => $issues->count(),
                'by_severity' => [
                    'critical' => $issues->where('severity', 'critical')->count(),
                    'high' => $issues->where('severity', 'high')->count(),
                    'medium' => $issues->where('severity', 'medium')->count(),
                    'low' => $issues->where('severity', 'low')->count(),
                ],
                'by_category' => $issues
                    ->groupBy('category')
                    ->map(fn ($group) => $group->count())
                    ->toArray(),
            ],

            'risk_profile' => [
                'has_critical' => $issues->where('severity', 'critical')->count() > 0,
                'high_ratio' => $issues->count() > 0
                    ? round($issues->where('severity', 'high')->count() / $issues->count(), 2)
                    : 0,
            ],

            'quality_signals' => [
                'issue_density' => $issues->count(),
                'confidence_avg' => round($issues->avg('confidence') ?? 0, 2),
            ],
        ];
    }
}