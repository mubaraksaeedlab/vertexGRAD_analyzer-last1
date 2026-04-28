<?php

namespace App\Modules\Analysis\Services;

use App\Modules\AI\Jobs\RunAiInsightGenerationJob;
use App\Modules\Analysis\Models\AnalysisRun;
use App\Modules\Analysis\Models\AnalysisRunFile;
use App\Modules\Languages\Registry\LanguageRegistry;
use App\Modules\Languages\Services\LanguageDetectionService;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectFile;
use App\Modules\Reports\Services\ReportService;
use App\Modules\Scores\Services\ScoringService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnalysisOrchestrator
{
    public function __construct(
        protected LanguageRegistry $registry,
        protected LanguageDetectionService $languageDetectionService,
        protected IssuePersistenceService $issuePersistenceService,
        protected ScoringService $scoringService,
        protected ReportService $reportService
    ) {
    }

    public function run(Project $project, AnalysisRun $analysisRun): array
    {
        $startedAt = now();

        $project->loadMissing('files');

        $analysisRun->update([
            'status' => 'running',
            'stage' => 'analysis',
            'started_at' => $startedAt,
            'progress_percent' => 0,
            'current_step' => 'Initializing analysis...',
            'current_file' => null,
            'processed_files' => 0,
            'total_files' => 0,
            'issues_found' => 0,
            'failure_reason' => null,
        ]);

        $files = $project->files;
        $filteredFiles = $this->filterFiles($files);
        $totalFiles = $filteredFiles->count();

        $analysisRun->update([
            'total_files' => $totalFiles,
            'current_step' => 'Preparing files...',
            'progress_percent' => $totalFiles > 0 ? 5 : 10,
        ]);

        $this->seedAnalysisRunFiles($analysisRun, $filteredFiles);

        if ($totalFiles === 0) {
            return $this->finishEmptyRun($project, $analysisRun, $startedAt);
        }

        $allIssues = [];

        $languages = $this->languageDetectionService->detectProjectLanguages($filteredFiles);
        $primaryLanguage = $this->languageDetectionService->detectPrimaryLanguage($filteredFiles);

        $analysisRun->update([
            'current_step' => 'Detecting languages...',
            'progress_percent' => 10,
        ]);

        $groupedFiles = $filteredFiles->groupBy(function ($file) {
            if (! $file instanceof ProjectFile) {
                return '__unknown__';
            }

            $language = $this->languageDetectionService->detectFileLanguage($file);

            return $language ?: '__unknown__';
        });

        $processed = 0;

        foreach ($groupedFiles as $language => $languageFiles) {
            $analyzer = $language !== '__unknown__'
                ? $this->registry->get($language)
                : null;

            if (! $analyzer) {
                foreach ($languageFiles as $file) {
                    if (! $file instanceof ProjectFile) {
                        continue;
                    }

                    $processed++;

                    $this->markFileAsCompleted(
                        analysisRun: $analysisRun,
                        file: $file,
                        processed: $processed,
                        totalFiles: $totalFiles,
                        currentStep: 'Skipping unsupported file type...'
                    );
                }

                continue;
            }

            foreach ($languageFiles as $file) {
                if (! $file instanceof ProjectFile) {
                    continue;
                }

                $this->markFileAsProcessing($analysisRun, $file, $processed, $totalFiles);

                try {
                    $issues = $analyzer->analyze($project, [$file]);

                    if (! empty($issues)) {
                        $allIssues = array_merge($allIssues, $issues);
                    }

                    $processed++;

                    $this->markFileAsCompleted(
                        analysisRun: $analysisRun,
                        file: $file,
                        processed: $processed,
                        totalFiles: $totalFiles,
                        currentStep: 'Analyzing files...'
                    );
                } catch (Throwable $e) {
                    Log::warning('AnalysisOrchestrator: file analysis failed', [
                        'project_id' => $project->id,
                        'analysis_run_id' => $analysisRun->id,
                        'project_file_id' => $file->id,
                        'relative_path' => $file->relative_path,
                        'language' => $language,
                        'error' => $e->getMessage(),
                    ]);

                    $processed++;

                    $this->markFileAsFailed(
                        analysisRun: $analysisRun,
                        file: $file,
                        processed: $processed,
                        totalFiles: $totalFiles
                    );
                }
            }
        }

        $pendingRows = AnalysisRunFile::where('analysis_run_id', $analysisRun->id)
            ->where('status', 'pending')
            ->get();

        foreach ($pendingRows as $pendingRow) {
            $file = $filteredFiles->firstWhere('id', $pendingRow->project_file_id);

            if (! $file instanceof ProjectFile) {
                continue;
            }

            $processed++;

            $this->markFileAsCompleted(
                analysisRun: $analysisRun,
                file: $file,
                processed: $processed,
                totalFiles: $totalFiles,
                currentStep: 'Finalizing remaining files...'
            );
        }

        $analysisRun->update([
            'current_step' => 'Saving issues...',
            'current_file' => null,
            'progress_percent' => 85,
            'processed_files' => min($processed, $totalFiles),
            'issues_found' => count($allIssues),
        ]);

        $this->issuePersistenceService->store($project, $analysisRun, $allIssues);

        $analysisRun->update([
            'current_step' => 'Calculating score...',
            'progress_percent' => 92,
        ]);

        $score = $this->scoringService->calculate($project, $analysisRun, $allIssues);

        $analysisRun->update([
            'current_step' => 'Generating report...',
            'progress_percent' => 97,
        ]);

        $finishedAt = now();
        $durationMs = $startedAt->diffInMilliseconds($finishedAt);

        $analysisRun->update([
            'status' => 'completed',
            'stage' => 'finished',
            'processed_files' => min($processed, $totalFiles),
            'issues_found' => count($allIssues),
            'finished_at' => $finishedAt,
            'duration_ms' => $durationMs,
            'progress_percent' => 100,
            'current_step' => 'Completed',
            'current_file' => null,
            'summary' => [
                'total_issues' => count($allIssues),
                'detected_languages' => array_keys($languages),
                'language_file_counts' => $languages,
                'primary_language' => $primaryLanguage,
                'overall_score' => $score->overall_score,
                'grade' => $score->grade,
            ],
        ]);

        $analysisRun->refresh();

        $report = $this->reportService->generateJsonReport(
            $project,
            $analysisRun,
            $score,
            $allIssues
        );

        $analysisRun->update([
            'summary' => array_merge($analysisRun->summary ?? [], [
                'report_id' => $report->id,
            ]),
        ]);

        $project->refresh();
        $existingSummary = is_array($project->summary) ? $project->summary : [];

        $project->update([
            'scan_status' => 'completed',
            'primary_language' => $primaryLanguage,
            'scanned_at' => $finishedAt,
            'last_activity_at' => $finishedAt,
            'summary' => array_merge($existingSummary, [
                'latest_run_id' => $analysisRun->id,
                'analysis_status' => 'completed',
                'issues_found' => count($allIssues),
                'overall_score' => $score->overall_score,
                'grade' => $score->grade,
                'report_id' => $report->id,
                'detected_languages' => array_keys($languages),
                'language_file_counts' => $languages,
                'primary_language' => $primaryLanguage,
                'analyzed_files_count' => min($processed, $totalFiles),
                'analysis_total_files' => $totalFiles,
                'analysis_completed_at' => $finishedAt->toDateTimeString(),
            ]),
        ]);

        $project->refresh();

        RunAiInsightGenerationJob::dispatch($analysisRun->id);

        $this->sendCompletedCallback(
            project: $project,
            analysisRun: $analysisRun,
            scoreValue: (float) $score->overall_score,
            grade: (string) $score->grade,
            issues: $allIssues,
            totalFiles: $totalFiles,
            primaryLanguage: $primaryLanguage,
            startedAt: $startedAt,
            finishedAt: $finishedAt
        );

        return $allIssues;
    }

    protected function finishEmptyRun(Project $project, AnalysisRun $analysisRun, $startedAt): array
    {
        $finishedAt = now();
        $durationMs = $startedAt->diffInMilliseconds($finishedAt);

        $emptySummary = [
            'total_issues' => 0,
            'detected_languages' => [],
            'language_file_counts' => [],
            'primary_language' => null,
            'overall_score' => 100,
            'grade' => 'A',
            'report_id' => null,
            'note' => 'No eligible source files were found for analysis.',
        ];

        $analysisRun->update([
            'status' => 'completed',
            'stage' => 'finished',
            'processed_files' => 0,
            'issues_found' => 0,
            'finished_at' => $finishedAt,
            'duration_ms' => $durationMs,
            'progress_percent' => 100,
            'current_step' => 'Completed',
            'current_file' => null,
            'summary' => $emptySummary,
        ]);

        $project->refresh();
        $existingSummary = is_array($project->summary) ? $project->summary : [];

        $project->update([
            'scan_status' => 'completed',
            'primary_language' => null,
            'scanned_at' => $finishedAt,
            'last_activity_at' => $finishedAt,
            'summary' => array_merge($existingSummary, [
                'latest_run_id' => $analysisRun->id,
                'analysis_status' => 'completed',
                'issues_found' => 0,
                'overall_score' => 100,
                'grade' => 'A',
                'report_id' => null,
                'detected_languages' => [],
                'language_file_counts' => [],
                'primary_language' => null,
                'analyzed_files_count' => 0,
                'analysis_total_files' => 0,
                'analysis_completed_at' => $finishedAt->toDateTimeString(),
            ]),
        ]);

        $project->refresh();

        RunAiInsightGenerationJob::dispatch($analysisRun->id);

        $this->sendCompletedCallback(
            project: $project,
            analysisRun: $analysisRun,
            scoreValue: 100.0,
            grade: 'A',
            issues: [],
            totalFiles: 0,
            primaryLanguage: null,
            startedAt: $startedAt,
            finishedAt: $finishedAt
        );

        return [];
    }

    protected function sendCompletedCallback(
        Project $project,
        AnalysisRun $analysisRun,
        float $scoreValue,
        string $grade,
        array $issues,
        int $totalFiles,
        ?string $primaryLanguage,
        $startedAt,
        $finishedAt
    ): void {
        if (empty($project->callback_url) || empty($project->platform_project_id)) {
            Log::warning('AnalysisOrchestrator: completed callback skipped, missing callback_url or platform_project_id', [
                'project_id' => $project->id,
                'callback_url' => $project->callback_url,
                'platform_project_id' => $project->platform_project_id,
            ]);
            return;
        }

        $severityCounts = $this->countIssueSeverities($issues);

        $payload = [
            'event' => 'scan.completed',
            'version' => '1.0',
            'project' => [
                'platform_project_id' => $project->platform_project_id,
                'scanner_project_id' => $project->id,
                'scanner_token' => $project->token,
                'name' => $project->name,
                'student_name' => $project->owner_name,
                'student_email' => $project->owner_email,
                'language' => $primaryLanguage,
            ],
            'scan' => [
                'status' => 'completed',
                'score' => round($scoreValue, 2),
                'grade' => $grade,
                'risk_level' => $this->calculateRiskLevel($scoreValue),
                'started_at' => $startedAt->toDateTimeString(),
                'completed_at' => $finishedAt->toDateTimeString(),
            ],
            'summary' => [
                'total_files' => $totalFiles,
                'issues_total' => count($issues),
                'critical' => $severityCounts['critical'],
                'high' => $severityCounts['high'],
                'medium' => $severityCounts['medium'],
                'low' => $severityCounts['low'],
            ],
            'highlights' => $this->buildHighlights($issues, $scoreValue, $grade),
            'recommendations' => $this->buildRecommendations($issues),
        ];

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'X-SCANNER-SECRET' => env('SCANNER_CALLBACK_SECRET'),
                    'Accept' => 'application/json',
                ])
                ->post($project->callback_url, $payload);

            Log::info('AnalysisOrchestrator: completed callback sent', [
                'project_id' => $project->id,
                'analysis_run_id' => $analysisRun->id,
                'platform_project_id' => $project->platform_project_id,
                'callback_url' => $project->callback_url,
                'http_status' => $response->status(),
                'response_body' => $response->body(),
                'score' => round($scoreValue, 2),
                'grade' => $grade,
                'issues_total' => count($issues),
            ]);
        } catch (Throwable $e) {
            Log::error('AnalysisOrchestrator: completed callback failed', [
                'project_id' => $project->id,
                'analysis_run_id' => $analysisRun->id,
                'callback_url' => $project->callback_url,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function countIssueSeverities(array $issues): array
    {
        $counts = [
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0,
        ];

        foreach ($issues as $issue) {
            $severity = strtolower((string) data_get($issue, 'severity', 'medium'));

            if (array_key_exists($severity, $counts)) {
                $counts[$severity]++;
            } else {
                $counts['medium']++;
            }
        }

        return $counts;
    }

    protected function buildHighlights(array $issues, float $scoreValue, string $grade): array
    {
        $highlights = [];

        $highlights[] = 'Analysis completed successfully.';
        $highlights[] = 'Overall score: ' . round($scoreValue, 2) . ' with grade ' . $grade . '.';

        $topIssues = array_slice($issues, 0, 3);

        foreach ($topIssues as $issue) {
            $title = data_get($issue, 'title')
                ?: data_get($issue, 'message')
                ?: data_get($issue, 'rule')
                ?: 'Issue detected';

            $highlights[] = (string) $title;
        }

        return array_values(array_unique($highlights));
    }

    protected function buildRecommendations(array $issues): array
    {
        $recommendations = [];

        foreach ($issues as $issue) {
            $fix = data_get($issue, 'fix')
                ?: data_get($issue, 'suggestion')
                ?: data_get($issue, 'recommendation');

            if ($fix) {
                $recommendations[] = (string) $fix;
            }

            if (count($recommendations) >= 5) {
                break;
            }
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Review the final analysis report and address the most severe findings first.';
            $recommendations[] = 'Improve code quality and reduce risk before final approval.';
        }

        return array_values(array_unique($recommendations));
    }

    protected function calculateRiskLevel(float $score): string
    {
        if ($score >= 85) {
            return 'low';
        }

        if ($score >= 70) {
            return 'medium';
        }

        return 'high';
    }

    protected function filterFiles(Collection $files): Collection
    {
        return $files->filter(function ($file) {
            if (! $file instanceof ProjectFile) {
                return false;
            }

            $name = strtolower((string) ($file->file_name ?? ''));
            $path = strtolower((string) ($file->relative_path ?? ''));
            $size = (int) ($file->size ?? 0);
            $extension = strtolower((string) ($file->extension ?? ''));

            if ((bool) ($file->is_binary ?? false)) {
                return false;
            }

            if ($path !== '' && (
                str_contains($path, 'vendor/') ||
                str_contains($path, 'node_modules/') ||
                str_contains($path, 'dist/') ||
                str_contains($path, 'build/')
            )) {
                return false;
            }

            if (
                str_ends_with($name, '.min.js') ||
                str_ends_with($name, '.min.css') ||
                str_ends_with($name, '.bundle.js')
            ) {
                return false;
            }

            if ($size > 300 * 1024) {
                return false;
            }

            $allowedExtensions = [
                'php', 'js', 'jsx', 'ts', 'tsx', 'py', 'java', 'cs', 'go', 'rb', 'rs',
                'cpp', 'c', 'h', 'hpp', 'swift', 'kt', 'kts', 'dart', 'vue', 'css',
                'scss', 'sass', 'less', 'html', 'htm', 'xml', 'json', 'yml', 'yaml',
                'md', 'sql', 'sh',
            ];

            return in_array($extension, $allowedExtensions, true);
        })->values();
    }

    protected function seedAnalysisRunFiles(AnalysisRun $analysisRun, Collection $files): void
    {
        AnalysisRunFile::where('analysis_run_id', $analysisRun->id)->delete();

        $now = now();

        $rows = $files->map(function ($file) use ($analysisRun, $now) {
            return [
                'analysis_run_id' => $analysisRun->id,
                'project_file_id' => $file->id,
                'status' => 'pending',
                'started_at' => null,
                'finished_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        if (! empty($rows)) {
            AnalysisRunFile::insert($rows);
        }
    }

    protected function markFileAsProcessing(
        AnalysisRun $analysisRun,
        ProjectFile $file,
        int $processed,
        int $totalFiles
    ): void {
        AnalysisRunFile::where('analysis_run_id', $analysisRun->id)
            ->where('project_file_id', $file->id)
            ->update([
                'status' => 'processing',
                'started_at' => now(),
                'updated_at' => now(),
            ]);

        $analysisRun->update([
            'current_step' => 'Analyzing files...',
            'current_file' => $file->relative_path,
            'processed_files' => $processed,
            'progress_percent' => $this->calculateProgress($processed, $totalFiles),
        ]);
    }

    protected function markFileAsCompleted(
        AnalysisRun $analysisRun,
        ProjectFile $file,
        int $processed,
        int $totalFiles,
        string $currentStep
    ): void {
        AnalysisRunFile::where('analysis_run_id', $analysisRun->id)
            ->where('project_file_id', $file->id)
            ->update([
                'status' => 'completed',
                'finished_at' => now(),
                'updated_at' => now(),
            ]);

        $analysisRun->update([
            'current_step' => $currentStep,
            'current_file' => $file->relative_path,
            'processed_files' => min($processed, $totalFiles),
            'progress_percent' => $this->calculateProgress($processed, $totalFiles),
        ]);
    }

    protected function markFileAsFailed(
        AnalysisRun $analysisRun,
        ProjectFile $file,
        int $processed,
        int $totalFiles
    ): void {
        AnalysisRunFile::where('analysis_run_id', $analysisRun->id)
            ->where('project_file_id', $file->id)
            ->update([
                'status' => 'failed',
                'finished_at' => now(),
                'updated_at' => now(),
            ]);

        $analysisRun->update([
            'current_step' => 'Analyzing files...',
            'current_file' => $file->relative_path,
            'processed_files' => min($processed, $totalFiles),
            'progress_percent' => $this->calculateProgress($processed, $totalFiles),
        ]);
    }

    protected function calculateProgress(int $processed, int $totalFiles): int
    {
        if ($totalFiles <= 0) {
            return 10;
        }

        return min(84, 10 + (int) floor(($processed / $totalFiles) * 74));
    }
}