<?php

namespace App\Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Reports\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Modules\AI\Services\AiReportChatService;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $query = Report::query()->latest();

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
        }

        if ($request->filled('analysis_run_id')) {
            $query->where('analysis_run_id', $request->integer('analysis_run_id'));
        }

        if ($request->filled('report_type')) {
            $query->where('report_type', (string) $request->string('report_type'));
        }

        if ($request->filled('q')) {
            $search = trim((string) $request->q);

            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('generator', 'like', "%{$search}%")
                    ->orWhere('version', 'like', "%{$search}%");
            });
        }

        $reports = $query->paginate(12)->withQueryString();

        return view('admin.reports.index', compact('reports'));
    }

    public function show(Report $report): View
    {
        return $this->buildReportView($report, 'admin.reports.show');
    }

    public function frontendShow(Report $report): View
    {
        return $this->buildReportView($report, 'frontend.reports.show');
    }

    protected function buildReportView(Report $report, string $view): View
    {
        $data = is_array($report->report_data)
            ? $report->report_data
            : (json_decode($report->report_data ?? '[]', true) ?: []);

        $issues = is_array($data['issues'] ?? null) ? $data['issues'] : [];
        $score = is_array($data['score'] ?? null) ? $data['score'] : [];
        $aiExecutiveInsight = (string) ($data['ai_executive_insight'] ?? '');

        $severitySummary = [
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0,
            'info' => 0,
        ];

        $categorySummary = [];
        $fileSummary = [];

        foreach ($issues as $issue) {
            $severity = strtolower((string) ($issue['severity'] ?? 'low'));
            $category = strtolower((string) ($issue['category'] ?? 'general'));
            $relativePath = $issue['metadata']['relative_path'] ?? 'unknown';

            if (array_key_exists($severity, $severitySummary)) {
                $severitySummary[$severity]++;
            } else {
                $severitySummary['low']++;
            }

            $categorySummary[$category] = ($categorySummary[$category] ?? 0) + 1;
            $fileSummary[$relativePath] = ($fileSummary[$relativePath] ?? 0) + 1;
        }

        arsort($fileSummary);
        arsort($categorySummary);

        $topRiskyFiles = collect($fileSummary)
            ->map(function ($count, $file) {
                return [
                    'file' => $file,
                    'issues_count' => $count,
                ];
            })
            ->values()
            ->take(10)
            ->all();

        $priorityIssues = collect($issues)
            ->sortByDesc(function ($issue) {
                return match (strtolower((string) ($issue['severity'] ?? 'low'))) {
                    'critical' => 5,
                    'high' => 4,
                    'medium' => 3,
                    'low' => 2,
                    default => 1,
                };
            })
            ->take(5)
            ->values()
            ->all();

        $scoreBreakdown = [
            'security_score' => (float) ($score['security_score'] ?? 0),
            'quality_score' => (float) ($score['quality_score'] ?? 0),
            'performance_score' => (float) ($score['performance_score'] ?? 0),
            'structure_score' => (float) ($score['structure_score'] ?? 0),
            'maintainability_score' => (float) ($score['maintainability_score'] ?? 0),
        ];

        return view($view, [
            'report' => $report,
            'data' => $data,
            'issues' => $issues,
            'score' => $score,
            'aiExecutiveInsight' => $aiExecutiveInsight,
            'severitySummary' => $severitySummary,
            'categorySummary' => $categorySummary,
            'scoreBreakdown' => $scoreBreakdown,
            'topRiskyFiles' => $topRiskyFiles,
            'priorityIssues' => $priorityIssues,
        ]);
    }

    public function api(Report $report): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => [
                'id' => $report->id,
                'project_id' => $report->project_id,
                'analysis_run_id' => $report->analysis_run_id,
                'report_type' => $report->report_type,
                'title' => $report->title,
                'file_path' => $report->file_path,
                'file_disk' => $report->file_disk,
                'file_size' => $report->file_size,
                'version' => $report->version,
                'generator' => $report->generator,
                'generated_at' => optional($report->generated_at)?->toDateTimeString(),
                'report_data' => $report->report_data,
            ],
        ]);
    }

    public function raw(Report $report): JsonResponse
    {
        return response()->json($report->report_data ?? []);
    }

    public function download(Report $report): StreamedResponse
    {
        $disk = $report->file_disk ?: 'local';
        $path = $report->file_path;

        abort_unless($path, 404, 'Report file path is missing.');
        abort_unless(Storage::disk($disk)->exists($path), 404, 'Report file not found.');

        $downloadName = 'report_' . $report->id . '.json';

        return Storage::disk($disk)->download(
            $path,
            $downloadName,
            ['Content-Type' => 'application/json']
        );
    }

    public function downloadPdf(Report $report): StreamedResponse
    {
        $pdfPath = data_get($report->report_data, 'pdf_file_path');
        $pdfDisk = data_get($report->report_data, 'pdf_file_disk', 'local');

        abort_unless($pdfPath, 404, 'PDF file path is missing.');
        abort_unless(Storage::disk($pdfDisk)->exists($pdfPath), 404, 'PDF report not found.');

        return Storage::disk($pdfDisk)->download(
            $pdfPath,
            'report_' . $report->id . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    public function frontendDetails(Report $report): View
    {
        $report->load([
            'project',
            'analysisRun.project',
            'analysisRun.score',
            'analysisRun.aiInsight',
            'analysisRun.issues.projectFile',
        ]);

        return view('frontend.reports.details', compact('report'));
    }
    public function askAi(Request $request, Report $report, AiReportChatService $aiReportChatService): JsonResponse
{
    $validated = $request->validate([
        "question" => ["required", "string", "max:2000"],
    ]);

    try {
        $answer = $aiReportChatService->ask($report, $validated["question"]);

        return response()->json([
            "ok" => true,
            "answer" => $answer,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            "ok" => false,
            "message" => "AI chat failed.",
            "error" => $e->getMessage(),
        ], 500);
    }
}
}