<?php

namespace App\Modules\Integrations\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Integrations\Jobs\SyncGitHubInstallationRepositoriesJob;
use App\Modules\Integrations\Models\GitHubWebhookDelivery;
use App\Modules\Integrations\Services\GitHubWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class GitHubWebhookController extends Controller
{
    public function __construct(
        protected GitHubWebhookService $webhookService
    ) {
    }

    public function handle(Request $request): JsonResponse
    {
        Log::info("GitHub webhook hit", [
            "headers" => $request->headers->all(),
            "payload" => $request->all(),
        ]);

        try {
            // مؤقتًا نوقف التحقق فقط للاختبار
            // $this->webhookService->verify($request);

            $event = (string) $request->header("X-GitHub-Event", "");
            $deliveryId = (string) $request->header("X-GitHub-Delivery", "");
            $payload = $request->all();

            Log::info("GitHub webhook parsed", [
                "event" => $event,
                "delivery_id" => $deliveryId,
                "installation_id" => data_get($payload, "installation.id"),
                "repository_id" => data_get($payload, "repository.id"),
                "action" => data_get($payload, "action"),
            ]);

            if (!$deliveryId) {
                return response()->json([
                    "ok" => false,
                    "message" => "Missing delivery ID",
                ], 400);
            }

            $existing = GitHubWebhookDelivery::where("delivery_id", $deliveryId)->first();

            if ($existing) {
                return response()->json([
                    "ok" => true,
                    "duplicate" => true,
                ]);
            }

            $delivery = GitHubWebhookDelivery::create([
                "delivery_id" => $deliveryId,
                "event" => $event,
                "action" => data_get($payload, "action"),
                "github_installation_id" => data_get($payload, "installation.id"),
                "github_repository_id" => data_get($payload, "repository.id"),
                "status" => "received",
                "payload" => $payload,
            ]);

            if (in_array($event, ["installation", "installation_repositories"])) {
                $installationId = (int) data_get($payload, "installation.id");

                if ($installationId > 0) {
                    SyncGitHubInstallationRepositoriesJob::dispatch($installationId, $delivery->id);
                }
            }

            $delivery->update([
                "status" => "queued",
            ]);

            return response()->json([
                "ok" => true,
            ]);
        } catch (Throwable $e) {
            Log::error("GitHub webhook failed", [
                "message" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);

            return response()->json([
                "ok" => false,
                "message" => $e->getMessage(),
            ], 400);
        }
    }
}