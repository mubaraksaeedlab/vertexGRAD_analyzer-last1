<?php

namespace App\Modules\Reports\Generators;

use App\Modules\Analysis\Models\AnalysisRun;
use App\Modules\Projects\Models\Project;
use App\Modules\Reports\Models\Report;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PdfReportGenerator
{
    public function generate(
        Report $report,
        Project $project,
        AnalysisRun $analysisRun,
        array $score,
        array $issues
    ): array {
        $data = [
            'project' => [
                'id' => $project->id,
                'uuid' => $project->uuid ?? null,
                'name' => $project->name,
                'primary_language' => $project->primary_language ?? data_get($project->summary, 'primary_language'),
                'scan_status' => $project->scan_status,
            ],
            'analysis_run' => [
                'id' => $analysisRun->id,
                'status' => $analysisRun->status,
                'stage' => $analysisRun->stage,
                'files_processed' => $analysisRun->files_processed,
                'issues_found' => $analysisRun->issues_found,
                'started_at' => optional($analysisRun->started_at)?->toDateTimeString(),
                'finished_at' => optional($analysisRun->finished_at)?->toDateTimeString(),
            ],
            'score' => $score,
            'issues' => $issues,
        ];

        $pdf = Pdf::loadView('admin.reports.pdf', [
            'report' => $report,
            'data' => $data,
        ])->setPaper('a4', 'portrait');

        $directory = 'private/reports/projects/' . $project->id;
        $filename = 'analysis_run_' . $analysisRun->id . '.pdf';
        $path = $directory . '/' . $filename;

        Storage::disk('local')->makeDirectory($directory);
        Storage::disk('local')->put($path, $pdf->output());

        return [
            'file_disk' => 'local',
            'file_path' => $path,
            'file_size' => Storage::disk('local')->size($path),
        ];
    }
}