<?php

namespace App\Modules\Reports\Services;

use App\Modules\AI\Services\AiExecutiveInsightService;
use App\Modules\Analysis\Models\AnalysisRun;
use App\Modules\Projects\Models\Project;
use App\Modules\Reports\Generators\JsonReportGenerator;
use App\Modules\Reports\Generators\PdfReportGenerator;
use App\Modules\Reports\Models\Report;
use App\Modules\Scores\Models\Score;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ReportService
{
    public function __construct(
        protected JsonReportGenerator $jsonReportGenerator,
        protected PdfReportGenerator $pdfReportGenerator,
        protected AiExecutiveInsightService $aiExecutiveInsightService
    ) {
    }

    public function generateJsonReport(
        Project $project,
        AnalysisRun $analysisRun,
        Score $score,
        array $issues
    ): Report {
        $reportData = $this->jsonReportGenerator->generate(
            $project,
            $analysisRun,
            $score,
            $issues
        );

        $directory = 'reports/projects/' . $project->id;
        $fileName = 'analysis_run_' . $analysisRun->id . '.json';
        $filePath = $directory . '/' . $fileName;

        Storage::disk('local')->makeDirectory($directory);

        $jsonContent = json_encode(
            $reportData,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($jsonContent === false) {
            $jsonContent = json_encode([
                'error' => 'Failed to encode report data to JSON.',
                'project_id' => $project->id,
                'analysis_run_id' => $analysisRun->id,
                'generated_at' => now()->toDateTimeString(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        Storage::disk('local')->put($filePath, $jsonContent);

        $fileSize = Storage::disk('local')->exists($filePath)
            ? Storage::disk('local')->size($filePath)
            : 0;

        $dbReportData = $this->buildDatabaseReportData($reportData);

        try {
            $aiExecutiveInsight = $this->aiExecutiveInsightService->generate(
                $this->buildAiExecutivePayload($project, $analysisRun, $score, $issues, $reportData)
            );

            $dbReportData['ai_executive_insight'] = $aiExecutiveInsight;
        } catch (Throwable $e) {
            Log::warning('ReportService: AI executive insight generation failed', [
                'project_id' => $project->id,
                'analysis_run_id' => $analysisRun->id,
                'error' => $e->getMessage(),
            ]);

            $dbReportData['ai_executive_insight'] = null;
            $dbReportData['ai_executive_insight_error'] = $e->getMessage();
        }

        $report = Report::updateOrCreate(
            [
                'analysis_run_id' => $analysisRun->id,
                'report_type' => 'json',
            ],
            [
                'project_id' => $project->id,
                'title' => 'JSON Analysis Report',
                'file_path' => $filePath,
                'file_disk' => 'local',
                'file_size' => $fileSize,
                'report_data' => $dbReportData,
                'version' => '1.1.0',
                'generator' => 'VertexGrad Analyzer',
                'generated_at' => now(),
            ]
        );

        try {
            $pdfMeta = $this->pdfReportGenerator->generate(
                report: $report,
                project: $project,
                analysisRun: $analysisRun,
                score: $this->normalizeScore($score),
                issues: $issues
            );

            $updatedReportData = array_merge($report->report_data ?? [], [
                'pdf_file_path' => $pdfMeta['file_path'] ?? null,
                'pdf_file_disk' => $pdfMeta['file_disk'] ?? 'local',
                'pdf_file_size' => $pdfMeta['file_size'] ?? null,
            ]);

            $report->update([
                'report_data' => $updatedReportData,
            ]);
        } catch (Throwable $e) {
            Log::warning('ReportService: PDF generation failed', [
                'project_id' => $project->id,
                'analysis_run_id' => $analysisRun->id,
                'error' => $e->getMessage(),
            ]);

            $updatedReportData = array_merge($report->report_data ?? [], [
                'pdf_file_path' => null,
                'pdf_file_disk' => 'local',
                'pdf_file_size' => null,
                'pdf_generation_error' => $e->getMessage(),
            ]);

            $report->update([
                'report_data' => $updatedReportData,
            ]);
        }

        return $report->fresh();
    }

    protected function buildDatabaseReportData(array $reportData): array
    {
        $issues = is_array($reportData['issues'] ?? null) ? $reportData['issues'] : [];

        return [
            'project' => is_array($reportData['project'] ?? null) ? $reportData['project'] : [],
            'analysis_run' => is_array($reportData['analysis_run'] ?? null) ? $reportData['analysis_run'] : [],
            'score' => is_array($reportData['score'] ?? null) ? $reportData['score'] : [],
            'issues_summary' => is_array($reportData['issues_summary'] ?? null)
                ? $reportData['issues_summary']
                : ['total' => count($issues)],
            'issues_preview' => array_slice($issues, 0, 20),
            'generated_at' => $reportData['generated_at'] ?? now()->toDateTimeString(),
            'generator' => $reportData['generator'] ?? 'VertexGrad Analyzer JSON Generator',
            'version' => $reportData['version'] ?? '1.1.0',
        ];
    }

    protected function normalizeScore(Score $score): array
    {
        return [
            'overall_score' => $score->overall_score ?? 0,
            'security_score' => $score->security_score ?? 0,
            'quality_score' => $score->quality_score ?? 0,
            'performance_score' => $score->performance_score ?? 0,
            'structure_score' => $score->structure_score ?? 0,
            'maintainability_score' => $score->maintainability_score ?? 0,
            'grade' => $score->grade ?? '-',
            'issues_count' => $score->issues_count ?? 0,
            'critical_count' => $score->critical_count ?? 0,
            'high_count' => $score->high_count ?? 0,
            'medium_count' => $score->medium_count ?? 0,
            'low_count' => $score->low_count ?? 0,
            'info_count' => $score->info_count ?? 0,
            'breakdown' => is_array($score->breakdown ?? null) ? $score->breakdown : [],
            'metadata' => is_array($score->metadata ?? null) ? $score->metadata : [],
        ];
    }

    protected function buildAiExecutivePayload(
        Project $project,
        AnalysisRun $analysisRun,
        Score $score,
        array $issues,
        array $reportData
    ): array {
        $issuesSummary = is_array($reportData['issues_summary'] ?? null) ? $reportData['issues_summary'] : [];
        $topIssues = is_array($reportData['issues'] ?? null) ? array_slice($reportData['issues'], 0, 3) : [];

        $topFindings = array_map(function (array $issue) {
            return [
                'title' => $issue['title'] ?? 'Untitled issue',
                'severity' => $issue['severity'] ?? 'Unknown',
            ];
        }, $topIssues);

        $highCount = (int) ($score->high_count ?? 0);
        $criticalCount = (int) ($score->critical_count ?? 0);
        $mediumCount = (int) ($score->medium_count ?? 0);

        $riskLevel = $criticalCount > 0
            ? 'CRITICAL'
            : ($highCount > 0
                ? 'HIGH'
                : ($mediumCount > 0 ? 'MEDIUM' : 'LOW'));

        $projectReadiness = $this->calculateProjectReadiness($score);

        $systemDecision = $analysisRun->status
            ? ucfirst((string) $analysisRun->status)
            : 'Completed';

        $primaryLanguage = $issuesSummary['primary_language']
            ?? $reportData['project']['primary_language']
            ?? $reportData['analysis_run']['primary_language']
            ?? 'unknown';

        return [
            'project_name' => $project->title ?? $project->name ?? ('Project #' . $project->id),
            'risk_level' => $riskLevel,
            'overall_score' => (string) ($score->overall_score ?? 0),
            'grade' => (string) ($score->grade ?? '-'),
            'issues_found' => (int) count($issues),
            'primary_language' => (string) $primaryLanguage,
            'project_readiness' => $projectReadiness . '%',
            'system_decision' => $systemDecision,
            'top_findings' => $topFindings,
        ];
    }

    protected function calculateProjectReadiness(Score $score): int
    {
        $readiness = 100;

        $readiness -= ((int) ($score->critical_count ?? 0)) * 30;
        $readiness -= ((int) ($score->high_count ?? 0)) * 20;
        $readiness -= ((int) ($score->medium_count ?? 0)) * 10;
        $readiness -= ((int) ($score->low_count ?? 0)) * 3;

        return max(0, min(100, $readiness));
    }
}