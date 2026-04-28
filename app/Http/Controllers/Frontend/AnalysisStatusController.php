<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Modules\Analysis\Models\AnalysisRun;
use Illuminate\Http\JsonResponse;

class AnalysisStatusController extends Controller
{
    public function show(AnalysisRun $analysisRun): JsonResponse
    {
        $analysisRun->load([
            'files.projectFile:id,file_name,relative_path',
            'project:id,summary'
        ]);

        $files = $analysisRun->files
            ->sortBy('id')
            ->take(200)
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'status' => $row->status,
                    'file_name' => $row->projectFile->file_name ?? null,
                    'relative_path' => $row->projectFile->relative_path ?? null,
                ];
            })
            ->values();

        if ($files->isEmpty()) {
            $files = collect([
                [
                    'id' => 0,
                    'status' => $analysisRun->status === 'completed' ? 'completed' : 'processing',
                    'file_name' => 'Analyzing project...',
                    'relative_path' => $analysisRun->current_file ?? 'Scanning...',
                ]
            ]);
        }

        $projectSummary = is_array($analysisRun->project?->summary)
            ? $analysisRun->project->summary
            : [];

        return response()->json([
            'ok' => true,
            'data' => [
                'id' => $analysisRun->id,
                'project_id' => $analysisRun->project_id,
                'status' => $analysisRun->status,
                'stage' => $analysisRun->stage,
                'progress_percent' => $analysisRun->progress_percent ?? 0,
                'current_step' => $analysisRun->current_step ?? 'Processing...',
                'current_file' => $analysisRun->current_file ?? '—',
                'processed_files' => $analysisRun->processed_files ?? 0,
                'total_files' => $analysisRun->total_files ?? 0,
                'issues_found' => $analysisRun->issues_found ?? 0,
                'summary' => [
                    'overall_score' => $projectSummary['overall_score'] ?? null,
                    'grade' => $projectSummary['grade'] ?? null,
                    'issues_found' => $projectSummary['issues_found'] ?? ($analysisRun->issues_found ?? 0),
                    'report_id' => $projectSummary['report_id'] ?? null,
                ],
                'files' => $files,
            ],
        ]);
    }
}