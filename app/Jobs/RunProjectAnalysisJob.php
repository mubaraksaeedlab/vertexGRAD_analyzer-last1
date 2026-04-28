<?php

namespace App\Jobs;

use App\Modules\Analysis\Models\AnalysisRun;
use App\Modules\Analysis\Services\AnalysisOrchestrator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunProjectAnalysisJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1200;
    public int $tries = 1;

    public function __construct(
        public int $analysisRunId
    ) {
    }

    public function handle(AnalysisOrchestrator $analysisOrchestrator): void
    {
        $analysisRun = AnalysisRun::with('project.files')->findOrFail($this->analysisRunId);

        $project = $analysisRun->project;

        if (!$project) {
            return;
        }

        $project->update([
            'scan_status' => 'running',
            'last_activity_at' => now(),
        ]);

        $analysisOrchestrator->run($project, $analysisRun);

        $analysisRun->refresh();
        $project->refresh();

        $summary = is_array($project->summary) ? $project->summary : [];

        $languageCounts = $project->files()
            ->selectRaw('language, COUNT(*) as total')
            ->whereNotNull('language')
            ->where('language', '!=', '')
            ->groupBy('language')
            ->orderByDesc('total')
            ->pluck('total', 'language');

        $primaryLanguage = $languageCounts->keys()->first();

        $runSummary = [];
        if (property_exists($analysisRun, 'summary') && is_array($analysisRun->summary)) {
            $runSummary = $analysisRun->summary;
        }

        $overallScore = $runSummary['overall_score']
            ?? $summary['overall_score']
            ?? null;

        $grade = $runSummary['grade']
            ?? $summary['grade']
            ?? null;

        $issuesFound = $runSummary['issues_found']
            ?? $analysisRun->issues_found
            ?? $summary['issues_found']
            ?? 0;

        $reportId = $runSummary['report_id']
            ?? $summary['report_id']
            ?? null;

        $processedFilesCount = $analysisRun->processed_files
            ?? $analysisRun->files_processed
            ?? 0;

        $totalFilesCount = $analysisRun->total_files ?? 0;

        $summary['overall_score'] = $overallScore;
        $summary['grade'] = $grade;
        $summary['issues_found'] = $issuesFound;
        $summary['report_id'] = $reportId;
        $summary['primary_language'] = $primaryLanguage;
        $summary['analyzed_files_count'] = $processedFilesCount;
        $summary['analysis_total_files'] = $totalFilesCount;
        $summary['latest_run_id'] = $analysisRun->id;
        $summary['analysis_status'] = $analysisRun->status;
        $summary['analysis_stage'] = $analysisRun->stage;
        $summary['analysis_completed_at'] = now()->toDateTimeString();

        $project->update([
            'scan_status' => $analysisRun->status === 'completed' ? 'completed' : $analysisRun->status,
            'primary_language' => $primaryLanguage,
            'summary' => $summary,
            'last_activity_at' => now(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $analysisRun = AnalysisRun::with('project')->find($this->analysisRunId);

        if (!$analysisRun) {
            return;
        }

        $analysisRun->update([
            'status' => 'failed',
            'stage' => 'failed',
            'current_step' => 'Analysis failed',
            'current_file' => null,
            'progress_percent' => 100,
            'failure_reason' => $exception->getMessage(),
            'finished_at' => now(),
        ]);

        $project = $analysisRun->project;

        if (!$project) {
            return;
        }

        $summary = is_array($project->summary) ? $project->summary : [];
        $summary['analysis_error'] = $exception->getMessage();
        $summary['analysis_failed_at'] = now()->toDateTimeString();
        $summary['latest_run_id'] = $analysisRun->id;
        $summary['analysis_status'] = 'failed';
        $summary['analysis_stage'] = 'failed';

        $project->update([
            'scan_status' => 'failed',
            'summary' => $summary,
            'last_activity_at' => now(),
        ]);
    }
}