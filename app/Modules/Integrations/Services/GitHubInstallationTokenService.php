<?php

namespace App\Modules\Integrations\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GitHubInstallationTokenService
{
    public function __construct(
        protected GitHubAppJwtService $jwtService
    ) {
    }

    public function getToken(int|string $installationId): string
    {
        $cacheKey = "github_installation_token_{$installationId}";

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($installationId) {
            $jwt = $this->jwtService->makeJwt();

            $response = Http::withToken($jwt)
                ->acceptJson()
                ->withHeaders([
                    'X-GitHub-Api-Version' => '2022-11-28',
                ])
                ->post(
                    rtrim(config('integrations.github.api_base'), '/') . "/app/installations/{$installationId}/access_tokens"
                );

            if ($response->failed()) {
                throw new RuntimeException('Failed to create GitHub installation token: ' . $response->body());
            }

            return (string) data_get($response->json(), 'token');
        });
    }
}