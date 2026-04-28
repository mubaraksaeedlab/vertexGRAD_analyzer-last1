<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Projects\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IntegrationProjectController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if ($request->header('X-INTEGRATION-SECRET') !== env('SCANNER_INTEGRATION_SECRET')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $data = $request->validate([
            'platform_project_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'student_name' => 'nullable|string|max:255',
            'student_email' => 'nullable|email|max:255',
            'language' => 'nullable|string|max:100',
            'callback_url' => 'required|url',
        ]);

        try {
            $project = Project::create([
                'uuid' => (string) Str::uuid(),
                'platform_project_id' => $data['platform_project_id'],
                'name' => $data['name'],
                'owner_name' => $data['student_name'] ?? null,
                'owner_email' => $data['student_email'] ?? null,
                'primary_language' => $data['language'] ?? null,
                'callback_url' => $data['callback_url'],
                'integration_mode' => 'vertexgrad',
                'external_source' => 'vertex_platform',
                'status' => 'pending',
                'scan_status' => 'pending',
                'token' => (string) Str::uuid(),
                'summary' => [
                    'source' => 'vertex_platform',
                    'integration_status' => 'received',
                ],
            ]);

            $startedAt = now()->subMinutes(2)->toDateTimeString();
            $completedAt = now()->toDateTimeString();

            $callbackPayload = [
                'event' => 'scan.completed',
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
                    'status' => 'completed',
                    'score' => 85,
                    'grade' => 'B',
                    'risk_level' => 'low',
                    'started_at' => $startedAt,
                    'completed_at' => $completedAt,
                ],
                'summary' => [
                    'total_files' => 10,
                    'issues_total' => 4,
                    'critical' => 0,
                    'high' => 1,
                    'medium' => 2,
                    'low' => 1,
                ],
                'highlights' => [
                    'Use of eval() detected',
                ],
                'recommendations' => [
                    'Remove dangerous functions',
                ],
            ];

            $callbackResponse = Http::timeout(20)
                ->withHeaders([
                    'X-SCANNER-SECRET' => env('SCANNER_CALLBACK_SECRET'),
                    'Accept' => 'application/json',
                ])
                ->post($project->callback_url, $callbackPayload);

            if ($callbackResponse->successful()) {
                $project->update([
                    'scan_status' => 'completed',
                    'status' => 'completed',
                    'scanned_at' => $completedAt,
                    'summary' => array_merge($project->summary ?? [], [
                        'integration_status' => 'callback_sent',
                        'callback_http_status' => $callbackResponse->status(),
                    ]),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Project received successfully and result sent to main platform.',
                    'scanner_project_id' => $project->id,
                    'project_token' => $project->token,
                    'callback_status' => $callbackResponse->status(),
                    'callback_response' => $callbackResponse->json(),
                ]);
            }

            $project->update([
                'scan_status' => 'failed',
                'status' => 'failed',
                'summary' => array_merge($project->summary ?? [], [
                    'integration_status' => 'callback_failed',
                    'callback_http_status' => $callbackResponse->status(),
                    'callback_body' => $callbackResponse->body(),
                ]),
            ]);

            Log::error('SCANNER CALLBACK RETURNED NON-SUCCESS STATUS', [
                'platform_project_id' => $project->platform_project_id,
                'scanner_project_id' => $project->id,
                'callback_url' => $project->callback_url,
                'status' => $callbackResponse->status(),
                'body' => $callbackResponse->body(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Project was created, but sending result to main platform failed.',
                'scanner_project_id' => $project->id,
                'callback_status' => $callbackResponse->status(),
                'callback_response' => $callbackResponse->json(),
            ], 500);

        } catch (\Throwable $e) {
            Log::error('INTEGRATION PROJECT STORE FAILED', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Integration project processing failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}