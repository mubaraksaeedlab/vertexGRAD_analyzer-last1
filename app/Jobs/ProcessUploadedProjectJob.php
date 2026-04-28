<?php

namespace App\Jobs;

use App\Modules\Projects\Models\Project;
use App\Modules\Uploads\Services\ArchiveExtractionService;
use App\Modules\Uploads\Services\FileDiscoveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessUploadedProjectJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries = 1;

    public function __construct(public int $projectId)
    {
    }

    public function handle(
        ArchiveExtractionService $archiveExtractionService,
        FileDiscoveryService $fileDiscoveryService
    ): void {
        $project = Project::find($this->projectId);

        if (!$project) {
            Log::warning('ProcessUploadedProjectJob: project not found', [
                'project_id' => $this->projectId,
            ]);
            return;
        }

        $summary = is_array($project->summary) ? $project->summary : [];
        $archivePath = $summary['archive_path'] ?? null;

        if (!$archivePath) {
            $summary['upload_status'] = 'failed';
            $summary['upload_error'] = 'Archive path is missing from project summary.';

            $project->update([
                'scan_status' => 'failed',
                'status' => 'failed',
                'summary' => $summary,
            ]);

            Log::error('ProcessUploadedProjectJob: archive path missing', [
                'project_id' => $project->id,
            ]);

            $this->sendFailedCallback($project, 'Archive path is missing from project summary.');

            return;
        }

        try {
            Log::info('ProcessUploadedProjectJob started', [
                'project_id' => $project->id,
                'archive_path' => $archivePath,
            ]);

            $summary['upload_status'] = 'extracting';
            $project->update([
                'scan_status' => 'processing',
                'summary' => $summary,
            ]);

            $extractedPath = $archiveExtractionService->extract($project, $archivePath);

            Log::info('ProcessUploadedProjectJob: extraction completed', [
                'project_id' => $project->id,
                'extracted_path' => $extractedPath,
            ]);

            $project->refresh();
            $summary = is_array($project->summary) ? $project->summary : [];
            $summary['upload_status'] = 'discovering_files';
            $summary['extracted_path'] = $extractedPath;

            $project->update([
                'summary' => $summary,
            ]);

            $discoveredFilesCount = $fileDiscoveryService->discoverAndStore($project, $extractedPath);

            Log::info('ProcessUploadedProjectJob: file discovery completed', [
                'project_id' => $project->id,
                'discovered_files_count' => $discoveredFilesCount,
            ]);

            $project->refresh();
            $summary = is_array($project->summary) ? $project->summary : [];
            $summary['upload_status'] = 'prepared';
            $summary['extracted_path'] = $extractedPath;
            $summary['discovered_files_count'] = $discoveredFilesCount;

            /*
             |---------------------------------------------------------------
             | مهم جدًا:
             | هذا الـ Job يجهّز المشروع فقط، وليس هو التحليل النهائي.
             | لذلك لا نعتبر المشروع completed إلا إذا كانت بيانات التحليل
             | الحقيقية موجودة بالفعل داخل summary / report.
             |---------------------------------------------------------------
             */
            $project->update([
                'scan_status' => 'pending',
                'status' => 'pending',
                'summary' => $summary,
            ]);

            Log::info('ProcessUploadedProjectJob finished successfully', [
                'project_id' => $project->id,
            ]);

            $project->refresh();

            $completedPayload = $this->buildCompletedPayloadFromRealData($project, $discoveredFilesCount);

            if ($completedPayload) {
                $project->update([
                    'scan_status' => 'completed',
                    'status' => 'completed',
                    'scanned_at' => now(),
                ]);

                $this->sendCallback($project, $completedPayload, 'completed');
                return;
            }

            $this->sendPreparedCallback($project, $discoveredFilesCount);
        } catch (Throwable $e) {
            Log::error('ProcessUploadedProjectJob failed', [
                'project_id' => $project->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $project->refresh();
            $summary = is_array($project->summary) ? $project->summary : [];
            $summary['upload_status'] = 'failed';
            $summary['upload_error'] = $e->getMessage();

            $project->update([
                'scan_status' => 'failed',
                'status' => 'failed',
                'summary' => $summary,
            ]);

            $extractDirectory = storage_path('app/private/extracted/project_' . $project->id);

            if (File::exists($extractDirectory)) {
                File::deleteDirectory($extractDirectory);
            }

            $this->sendFailedCallback($project, $e->getMessage());

            throw $e;
        }
    }

    protected function buildCompletedPayloadFromRealData(Project $project, int $discoveredFilesCount): ?array
    {
        $summary = is_array($project->summary) ? $project->summary : [];

        /*
         |---------------------------------------------------------------
         | نحاول نقرأ البيانات الحقيقية من summary أولاً
         |---------------------------------------------------------------
         */
        $overallScore = $summary['overall_score'] ?? null;
        $grade = $summary['grade'] ?? null;
        $issuesTotal = $summary['issues_found'] ?? null;
        $primaryLanguage = $summary['primary_language'] ?? $project->primary_language;
        $latestRunId = $summary['latest_run_id'] ?? null;
        $reportId = $summary['report_id'] ?? null;

        /*
         |---------------------------------------------------------------
         | إذا ما كانت موجودة في summary نحاول نقرأها من آخر تقرير
         |---------------------------------------------------------------
         */
        $reportData = [];
        try {
            if (method_exists($project, 'reports')) {
                $latestReport = $project->reports()->latest('id')->first();

                if ($latestReport && is_array($latestReport->report_data ?? null)) {
                    $reportData = $latestReport->report_data;
                }
            }
        } catch (Throwable $e) {
            Log::warning('ProcessUploadedProjectJob: unable to read latest report data', [
                'project_id' => $project->id,
                'message' => $e->getMessage(),
            ]);
        }

        if ($overallScore === null) {
            $overallScore = data_get($reportData, 'overall_score')
                ?? data_get($reportData, 'score.overall')
                ?? data_get($reportData, 'score.overall_score');
        }

        if ($grade === null) {
            $grade = data_get($reportData, 'grade')
                ?? data_get($reportData, 'score.grade');
        }

        if ($issuesTotal === null) {
            $issuesTotal = data_get($reportData, 'issues_found')
                ?? data_get($reportData, 'summary.issues_total');

            if ($issuesTotal === null && is_array(data_get($reportData, 'issues'))) {
                $issuesTotal = count(data_get($reportData, 'issues', []));
            }
        }

        $critical = $summary['critical'] ?? data_get($reportData, 'summary.critical', 0);
        $high = $summary['high'] ?? data_get($reportData, 'summary.high', 0);
        $medium = $summary['medium'] ?? data_get($reportData, 'summary.medium', 0);
        $low = $summary['low'] ?? data_get($reportData, 'summary.low', 0);

        $highlights = $summary['highlights'] ?? data_get($reportData, 'highlights', []);
        $recommendations = $summary['recommendations'] ?? data_get($reportData, 'recommendations', []);

        if (!is_array($highlights)) {
            $highlights = [];
        }

        if (!is_array($recommendations)) {
            $recommendations = [];
        }

        /*
         |---------------------------------------------------------------
         | إذا لم توجد نتيجة تحليل حقيقية، نرجع null
         |---------------------------------------------------------------
         */
        if ($overallScore === null || $grade === null || $issuesTotal === null) {
            Log::info('ProcessUploadedProjectJob: real analysis data not available yet, completed callback deferred', [
                'project_id' => $project->id,
                'overall_score' => $overallScore,
                'grade' => $grade,
                'issues_total' => $issuesTotal,
                'latest_run_id' => $latestRunId,
                'report_id' => $reportId,
            ]);

            return null;
        }

        $overallScore = (float) $overallScore;
        $issuesTotal = (int) $issuesTotal;

        $riskLevel = $this->calculateRiskLevel($overallScore);

        if (empty($highlights)) {
            $highlights = [
                'Real analysis completed successfully.',
                'Analysis results were extracted from the actual project data.',
            ];
        }

        if (empty($recommendations)) {
            $recommendations = [
                'Review the detailed report for issue-by-issue remediation.',
                'Address higher-severity findings first.',
            ];
        }

        return [
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
                'score' => $overallScore,
                'grade' => (string) $grade,
                'risk_level' => $riskLevel,
                'started_at' => optional($project->created_at)->toDateTimeString() ?? now()->subMinutes(2)->toDateTimeString(),
                'completed_at' => now()->toDateTimeString(),
            ],
            'summary' => [
                'total_files' => $discoveredFilesCount,
                'issues_total' => $issuesTotal,
                'critical' => (int) $critical,
                'high' => (int) $high,
                'medium' => (int) $medium,
                'low' => (int) $low,
            ],
            'highlights' => array_values($highlights),
            'recommendations' => array_values($recommendations),
        ];
    }

    protected function sendPreparedCallback(Project $project, int $discoveredFilesCount): void
    {
        if (empty($project->callback_url) || empty($project->platform_project_id)) {
            Log::warning('ProcessUploadedProjectJob: prepared callback skipped, missing callback_url or platform_project_id', [
                'project_id' => $project->id,
                'callback_url' => $project->callback_url,
                'platform_project_id' => $project->platform_project_id,
            ]);
            return;
        }

        $payload = [
            'event' => 'scan.prepared',
            'version' => '1.0',
            'project' => [
                'platform_project_id' => $project->platform_project_id,
                'scanner_project_id' => $project->id,
                'scanner_token' => $project->token,
                'name' => $project->name,
                'student_name' => $project->owner_name,
                'student_email' => $project->owner_email,
                'language' => $project->primary_language,
            ],
            'scan' => [
                'status' => 'prepared',
                'score' => null,
                'grade' => null,
                'risk_level' => null,
                'started_at' => optional($project->created_at)->toDateTimeString() ?? now()->toDateTimeString(),
                'completed_at' => now()->toDateTimeString(),
            ],
            'summary' => [
                'total_files' => $discoveredFilesCount,
                'issues_total' => null,
                'critical' => 0,
                'high' => 0,
                'medium' => 0,
                'low' => 0,
            ],
            'highlights' => [
                'Project archive extracted successfully.',
                'Source files were discovered and linked successfully.',
            ],
            'recommendations' => [
                'Run the actual analysis to generate real technical results.',
            ],
        ];

        $this->sendCallback($project, $payload, 'prepared');
    }

    protected function sendFailedCallback(Project $project, string $reason): void
    {
        if (empty($project->callback_url) || empty($project->platform_project_id)) {
            Log::warning('ProcessUploadedProjectJob: failed callback skipped, missing callback_url or platform_project_id', [
                'project_id' => $project->id,
                'callback_url' => $project->callback_url,
                'platform_project_id' => $project->platform_project_id,
            ]);
            return;
        }

        $payload = [
            'event' => 'scan.failed',
            'version' => '1.0',
            'project' => [
                'platform_project_id' => $project->platform_project_id,
                'scanner_project_id' => $project->id,
                'scanner_token' => $project->token,
                'name' => $project->name,
                'student_name' => $project->owner_name,
                'student_email' => $project->owner_email,
                'language' => $project->primary_language,
            ],
            'scan' => [
                'status' => 'failed',
                'score' => null,
                'grade' => null,
                'risk_level' => 'unknown',
                'started_at' => optional($project->created_at)->toDateTimeString() ?? now()->subMinutes(2)->toDateTimeString(),
                'completed_at' => now()->toDateTimeString(),
            ],
            'summary' => [
                'total_files' => 0,
                'issues_total' => 0,
                'critical' => 0,
                'high' => 0,
                'medium' => 0,
                'low' => 0,
            ],
            'highlights' => [],
            'recommendations' => [
                'Scan failed. Please retry.',
                'Reason: ' . $reason,
            ],
        ];

        $this->sendCallback($project, $payload, 'failed');
    }

    protected function sendCallback(Project $project, array $payload, string $type): void
    {
        $response = Http::timeout(20)
            ->withHeaders([
                'X-SCANNER-SECRET' => env('SCANNER_CALLBACK_SECRET'),
                'Accept' => 'application/json',
            ])
            ->post($project->callback_url, $payload);

        Log::info("ProcessUploadedProjectJob: {$type} callback sent", [
            'project_id' => $project->id,
            'platform_project_id' => $project->platform_project_id,
            'callback_url' => $project->callback_url,
            'http_status' => $response->status(),
            'response_body' => $response->body(),
        ]);
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
}