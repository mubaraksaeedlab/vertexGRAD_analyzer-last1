<?php

namespace App\Modules\Uploads\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessUploadedProjectJob;
use App\Modules\Projects\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UploadController extends Controller
{
    public function index(Request $request): View
    {
        return view('frontend.upload.index', [
            'platformProjectId' => $request->input('platform_project_id'),
            'projectName' => $request->input('project_name'),
            'studentName' => $request->input('student_name'),
            'studentEmail' => $request->input('student_email'),
            'language' => $request->input('language'),
            'callbackUrl' => $request->input('callback_url'),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $isAjax = $request->ajax()
            || $request->wantsJson()
            || $request->header('X-Requested-With') === 'XMLHttpRequest';

        $validator = Validator::make($request->all(), [
            'platform_project_id' => ['nullable', 'integer'],
            'callback_url' => ['nullable', 'url', 'max:1000'],

            'project_name' => ['required', 'string', 'max:255'],
            'student_name' => ['nullable', 'string', 'max:255'],
            'student_email' => ['nullable', 'email', 'max:255'],
            'language' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'project_zip' => ['required', 'file', 'mimes:zip,rar', 'max:51200'],
        ]);

        if ($validator->fails()) {
            if ($isAjax) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();
        $uploadedFile = $request->file('project_zip');

        if (!$uploadedFile) {
            $message = 'No project archive was uploaded.';

            if ($isAjax) {
                return response()->json([
                    'ok' => false,
                    'message' => $message,
                    'errors' => [
                        'project_zip' => [$message],
                    ],
                ], 422);
            }

            return back()->withErrors([
                'project_zip' => $message,
            ])->withInput();
        }

        $originalName = $uploadedFile->getClientOriginalName();
        $extension = strtolower($uploadedFile->getClientOriginalExtension());

        if (!in_array($extension, ['zip', 'rar'], true)) {
            $message = 'The uploaded archive must be a ZIP or RAR file.';

            if ($isAjax) {
                return response()->json([
                    'ok' => false,
                    'message' => $message,
                    'errors' => [
                        'project_zip' => [$message],
                    ],
                ], 422);
            }

            return back()->withErrors([
                'project_zip' => $message,
            ])->withInput();
        }

        $storedPath = $uploadedFile->store('archives', 'local');

        $project = Project::create([
            'uuid' => (string) Str::uuid(),
            'name' => $validated['project_name'],
            'description' => $validated['description'] ?? null,
            'primary_language' => $validated['language'] ?? null,
            'token' => Str::random(32),

            // الربط مع المنصة الرئيسية
            'platform_project_id' => $validated['platform_project_id'] ?? null,
            'callback_url' => $validated['callback_url'] ?? null,
            'integration_mode' => !empty($validated['platform_project_id']) ? 'vertexgrad' : null,
            'external_source' => !empty($validated['platform_project_id']) ? 'vertex_platform' : null,

            // بيانات المالك
            'owner_name' => $validated['student_name'] ?? null,
            'owner_email' => $validated['student_email'] ?? null,

            // حالة التحليل
            'scan_status' => 'pending',
            'status' => 'pending',

            'summary' => [
                'student_name' => $validated['student_name'] ?? null,
                'student_email' => $validated['student_email'] ?? null,
                'archive_path' => $storedPath,
                'original_file_name' => $originalName,
                'archive_extension' => $extension,
                'upload_status' => 'queued',
                'discovered_files_count' => 0,
                'source' => !empty($validated['platform_project_id']) ? 'vertex_platform' : 'direct_upload',
            ],
        ]);

        ProcessUploadedProjectJob::dispatch($project->id);

        $redirectUrl = route('frontend.projects.show', $project);

        if ($isAjax) {
            return response()->json([
                'ok' => true,
                'message' => 'Project uploaded successfully. Preparation has started in the background.',
                'redirect_url' => $redirectUrl,
                'project_id' => $project->id,
            ]);
        }

        return redirect()
            ->route('frontend.projects.show', $project)
            ->with('success', 'Project uploaded successfully. Preparation has started in the background.');
    }
}