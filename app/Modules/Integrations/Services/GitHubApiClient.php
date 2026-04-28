<?php

namespace App\Modules\Integrations\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class GitHubApiClient
{
    public function __construct(
        protected GitHubAppJwtService $jwtService,
        protected GitHubInstallationTokenService $installationTokenService
    ) {
    }

    public function asApp(): PendingRequest
    {
        $jwt = $this->jwtService->makeJwt();

        return Http::withToken($jwt)
            ->acceptJson()
            ->withHeaders([
                "X-GitHub-Api-Version" => "2022-11-28",
            ])
            ->baseUrl(rtrim(config("integrations.github.api_base"), "/"))
            ->retry(3, 500);
    }

    public function forInstallation(int|string $installationId): PendingRequest
    {
        $token = $this->installationTokenService->getToken($installationId);

        return Http::withToken($token)
            ->acceptJson()
            ->withHeaders([
                "X-GitHub-Api-Version" => "2022-11-28",
            ])
            ->baseUrl(rtrim(config("integrations.github.api_base"), "/"))
            ->retry(3, 500);
    }
}



