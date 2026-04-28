<?php

namespace App\Modules\Integrations\Services;

use Firebase\JWT\JWT;
use RuntimeException;

class GitHubAppJwtService
{
    public function makeJwt(): string
    {
        $appId = config('integrations.github.app_id');
        $privateKey = config('integrations.github.private_key');
        $privateKeyPath = config('integrations.github.private_key_path');

        if (!$privateKey && $privateKeyPath) {
            $fullPath = base_path($privateKeyPath);

            if (!file_exists($fullPath)) {
                throw new RuntimeException("GitHub private key file not found at: {$fullPath}");
            }

            $privateKey = file_get_contents($fullPath);
        }

        if (!$appId || !$privateKey) {
            throw new RuntimeException('GitHub App configuration is missing.');
        }

        $now = time();

        $payload = [
            'iat' => $now - 60,
            'exp' => $now + (9 * 60),
            'iss' => (string) $appId,
        ];

        return JWT::encode($payload, $privateKey, 'RS256');
    }
}