<?php

namespace App\Modules\Integrations\Jobs;

use App\Modules\Integrations\Models\GitHubInstallation;
use App\Modules\Integrations\Models\GitHubRepository;
use App\Modules\Integrations\Models\GitHubWebhookDelivery;
use App\Modules\Integrations\Services\GitHubApiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Throwable;

class SyncGitHubInstallationRepositoriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $installationId,
        public ?int $deliveryDbId = null
    ) {
    }

    public function handle(GitHubApiClient $client): void
    {
        $installationResponse = $client->forInstallation($this->installationId)
            ->get("/app/installations/{$this->installationId}");

        if ($installationResponse->failed()) {
            throw new \RuntimeException("Failed to fetch installation: " . $installationResponse->body());
        }

        $installationData = $installationResponse->json();

        GitHubInstallation::updateOrCreate(
            ["github_installation_id" => $this->installationId],
            [
                "github_app_id" => data_get($installationData, "app_id"),
                "account_type" => data_get($installationData, "account.type"),
                "account_id" => data_get($installationData, "account.id"),
                "account_login" => data_get($installationData, "account.login"),
                "account_avatar_url" => data_get($installationData, "account.avatar_url"),
                "target_type" => data_get($installationData, "target_type"),
                "target_id" => data_get($installationData, "target_id"),
                "permissions" => data_get($installationData, "permissions"),
                "events" => data_get($installationData, "events"),
                "suspended_at" => data_get($installationData, "suspended_at"),
                "last_synced_at" => now(),
            ]
        );

        $reposResponse = $client->forInstallation($this->installationId)
            ->get("/installation/repositories");

        if ($reposResponse->failed()) {
            throw new \RuntimeException("Failed to fetch installation repositories: " . $reposResponse->body());
        }

        $repositories = data_get($reposResponse->json(), "repositories", []);

        foreach ($repositories as $repo) {
            GitHubRepository::updateOrCreate(
                ["github_repository_id" => data_get($repo, "id")],
                [
                    "github_installation_id" => $this->installationId,
                    "full_name" => data_get($repo, "full_name"),
                    "owner" => data_get($repo, "owner.login"),
                    "name" => data_get($repo, "name"),
                    "is_private" => (bool) data_get($repo, "private", false),
                    "default_branch" => data_get($repo, "default_branch"),
                    "language" => data_get($repo, "language"),
                    "html_url" => data_get($repo, "html_url"),
                    "clone_url" => data_get($repo, "clone_url"),
                    "last_pushed_at" => data_get($repo, "pushed_at") ? Carbon::parse(data_get($repo, "pushed_at")) : null,
                    "is_active" => true,
                    "metadata" => $repo,
                ]
            );
        }

        if ($this->deliveryDbId) {
            GitHubWebhookDelivery::where("id", $this->deliveryDbId)->update([
                "status" => "processed",
                "processed_at" => now(),
            ]);
        }
    }

    public function failed(Throwable $e): void
    {
        if ($this->deliveryDbId) {
            GitHubWebhookDelivery::where("id", $this->deliveryDbId)->update([
                "status" => "failed",
                "error_message" => $e->getMessage(),
            ]);
        }
    }
}