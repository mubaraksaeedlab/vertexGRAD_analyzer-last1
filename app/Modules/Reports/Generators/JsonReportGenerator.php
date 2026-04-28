<?php

namespace App\Modules\Reports\Generators;

use App\Modules\Projects\Models\Project;
use App\Modules\Analysis\Models\AnalysisRun;
use App\Modules\Scores\Models\Score;

class JsonReportGenerator
{
    public function generate(Project $project, AnalysisRun $analysisRun, Score $score, array $issues): array
    {
        return [
            'project' => [
                'id' => $project->id,
                'uuid' => $project->uuid,
                'name' => $project->name,
                'primary_language' => $project->primary_language,
                'detected_languages' => $project->detected_languages,
                'scan_status' => $project->scan_status,
            ],

            'analysis_run' => [
                'id' => $analysisRun->id,
                'run_uuid' => $analysisRun->run_uuid,
                'status' => $analysisRun->status,
                'stage' => $analysisRun->stage,
                'files_processed' => $analysisRun->files_processed,
                'issues_found' => $analysisRun->issues_found,
                'started_at' => optional($analysisRun->started_at)?->toDateTimeString(),
                'finished_at' => optional($analysisRun->finished_at)?->toDateTimeString(),
            ],

            'score' => [
                'overall_score' => (float) $score->overall_score,
                'security_score' => (float) $score->security_score,
                'quality_score' => (float) $score->quality_score,
                'performance_score' => (float) $score->performance_score,
                'structure_score' => (float) $score->structure_score,
                'maintainability_score' => (float) $score->maintainability_score,
                'grade' => $score->grade,
                'issues_count' => $score->issues_count,
                'critical_count' => $score->critical_count,
                'high_count' => $score->high_count,
                'medium_count' => $score->medium_count,
                'low_count' => $score->low_count,
                'info_count' => $score->info_count,
                'breakdown' => $score->breakdown,
            ],

            'issues' => array_map(function ($issue) {
                return [
                    'rule_code' => $issue['rule_code'] ?? null,
                    'category' => $issue['category'] ?? null,
                    'severity' => $issue['severity'] ?? null,
                    'language' => $issue['language'] ?? null,
                    'title' => $issue['title'] ?? null,
                    'description' => $issue['description'] ?? null,
                    'recommendation' => $issue['recommendation'] ?? null,
                    'line_start' => $issue['line_start'] ?? null,
                    'line_end' => $issue['line_end'] ?? null,
                    'snippet' => $issue['snippet'] ?? null,
                    'metadata' => $issue['metadata'] ?? null,
                ];
            }, $issues),

            'generated_at' => now()->toDateTimeString(),
            'generator' => 'VertexGrad Analyzer JSON Generator',
            'version' => '1.0.0',
        ];
    }
}