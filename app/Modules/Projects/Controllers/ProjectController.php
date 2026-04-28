<?php

namespace App\Modules\Projects\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\RunProjectAnalysisJob;
use App\Modules\Analysis\Models\AnalysisRun;
use App\Modules\Projects\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        $projects = Project::latest()->paginate(12);

        return view('admin.projects.index', compact('projects'));
    }

    public function show(Project $project): View
    {
        $project->load(['files']);

        return view('admin.projects.show', compact('project'));
    }

    public function frontendShow(Project $project): View
    {
        $project->load(['files']);

        $summary = is_array($project->summary) ? $project->summary : [];

        return view('frontend.projects.show', [
            'project' => $project,
            'summary' => $summary,
        ]);
    }

    public function preparationStatus(Project $project): JsonResponse
    {
        try {
            $project->refresh();
            $project->load(['files']);

            $summary = is_array($project->summary) ? $project->summary : [];

            $latestRun = AnalysisRun::where('project_id', $project->id)
                ->latest('id')
                ->first();

            $files = $project->files->map(function ($file) {
                return [
                    'id' => $file->id,
                    'file_name' => $file->file_name,
                    'extension' => $file->extension,
                    'language' => $file->language,
                    'category' => $file->category,
                    'relative_path' => $file->relative_path,
                ];
            })->values();

            $uploadStatus = strtolower($summary['upload_status'] ?? 'unknown');
            $filesCount = (int) ($summary['discovered_files_count'] ?? $project->files->count());

            $diagnostics = [
                'project_id' => $project->id,
                'project_scan_status' => $project->scan_status,
                'upload_status' => $uploadStatus,
                'files_count_from_relation' => $project->files->count(),
                'files_count_from_summary' => (int) ($summary['discovered_files_count'] ?? 0),
                'latest_run_id' => $latestRun?->id,
                'latest_run_status' => $latestRun?->status,
                'latest_run_stage' => $latestRun?->stage,
                'latest_run_progress_percent' => $latestRun?->progress_percent,
                'latest_run_current_step' => $latestRun?->current_step,
                'latest_run_failure_reason' => $latestRun?->failure_reason,
                'has_files' => $project->files->isNotEmpty(),
                'is_ready_for_analysis' => $uploadStatus === 'prepared' || $filesCount > 0,
                'server_time' => now()->toDateTimeString(),
            ];

            return response()->json([
                'ok' => true,
                'data' => [
                    'scan_status' => $project->scan_status,
                    'summary' => $summary,
                    'files' => $files,
                    'latest_run' => $latestRun ? [
                        'id' => $latestRun->id,
                        'status' => $latestRun->status,
                        'stage' => $latestRun->stage,
                        'progress_percent' => $latestRun->progress_percent,
                        'current_step' => $latestRun->current_step,
                        'current_file' => $latestRun->current_file,
                        'total_files' => $latestRun->total_files,
                        'processed_files' => $latestRun->processed_files,
                        'issues_found' => $latestRun->issues_found,
                        'failure_reason' => $latestRun->failure_reason,
                        'queued_at' => optional($latestRun->queued_at)?->toDateTimeString(),
                        'started_at' => optional($latestRun->started_at)?->toDateTimeString(),
                        'finished_at' => optional($latestRun->finished_at)?->toDateTimeString(),
                    ] : null,
                    'diagnostics' => $diagnostics,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Project preparationStatus failed', [
                'project_id' => $project->id ?? null,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Failed to load preparation status.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function runAnalysis(Request $request, Project $project): RedirectResponse|JsonResponse
    {
        $isAjax = $request->ajax()
            || $request->wantsJson()
            || $request->header('X-Requested-With') === 'XMLHttpRequest';

        $project->load(['files']);

        $summary = is_array($project->summary) ? $project->summary : [];
        $uploadStatus = strtolower($summary['upload_status'] ?? 'queued');

        if ($project->files->isEmpty()) {
            $message = 'No project files were discovered, so analysis cannot start.';

            if ($isAjax) {
                return response()->json([
                    'ok' => false,
                    'message' => $message,
                    'debug' => [
                        'project_id' => $project->id,
                        'scan_status' => $project->scan_status,
                        'upload_status' => $uploadStatus,
                        'files_count' => $project->files->count(),
                        'summary' => $summary,
                    ],
                ], 422);
            }

            return redirect()
                ->route('frontend.projects.show', $project)
                ->withErrors([
                    'project' => $message,
                ]);
        }

        if ($uploadStatus !== 'prepared' && $project->files->count() <= 0) {
            $message = 'Project files are still being prepared. Please wait a moment and try again.';

            if ($isAjax) {
                return response()->json([
                    'ok' => false,
                    'message' => $message,
                    'debug' => [
                        'project_id' => $project->id,
                        'scan_status' => $project->scan_status,
                        'upload_status' => $uploadStatus,
                        'files_count' => $project->files->count(),
                    ],
                ], 422);
            }

            return redirect()
                ->route('frontend.projects.show', $project)
                ->withErrors([
                    'project' => $message,
                ]);
        }

        $hasRunningAnalysis = AnalysisRun::where('project_id', $project->id)
            ->whereIn('status', ['queued', 'running'])
            ->exists();

        if ($hasRunningAnalysis) {
            $message = 'An analysis is already in progress for this project.';

            if ($isAjax) {
                return response()->json([
                    'ok' => false,
                    'message' => $message,
                ], 422);
            }

            return redirect()
                ->route('frontend.projects.show', $project)
                ->withErrors([
                    'project' => $message,
                ]);
        }

        $analysisRun = AnalysisRun::create([
            'project_id' => $project->id,
            'run_uuid' => (string) Str::uuid(),
            'trigger_type' => 'manual',
            'triggered_by_type' => null,
            'triggered_by_id' => null,
            'status' => 'queued',
            'stage' => 'queued',
            'progress_percent' => 0,
            'current_step' => 'Queued for analysis',
            'current_file' => null,
            'total_files' => 0,
            'processed_files' => 0,
            'analyzer_version' => '1.0.0',
            'engine_name' => 'VertexGrad Analyzer',
            'issues_found' => 0,
            'duration_ms' => 0,
            'failure_reason' => null,
            'summary' => null,
            'metrics' => null,
            'context' => null,
            'queued_at' => now(),
            'started_at' => null,
            'finished_at' => null,
        ]);

        $summary['latest_run_id'] = $analysisRun->id;
        $summary['analysis_status'] = 'queued';

        $project->update([
            'scan_status' => 'running',
            'last_activity_at' => now(),
            'summary' => $summary,
        ]);

        RunProjectAnalysisJob::dispatch($analysisRun->id);

        $statusUrl = route('frontend.analysis-runs.status', $analysisRun);
        $redirectUrl = route('frontend.projects.show', $project->fresh());

        if ($isAjax) {
            return response()->json([
                'ok' => true,
                'message' => 'Analysis started successfully.',
                'run_id' => $analysisRun->id,
                'project_id' => $project->id,
                'status_url' => $statusUrl,
                'redirect_url' => $redirectUrl,
            ]);
        }

        return redirect()
            ->route('frontend.projects.show', $project->fresh())
            ->with('success', 'Analysis started successfully.');
    }
}