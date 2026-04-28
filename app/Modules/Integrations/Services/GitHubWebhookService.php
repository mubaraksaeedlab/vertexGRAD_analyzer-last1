<?php

namespace App\Modules\Integrations\Services;

use Illuminate\Http\Request;
use RuntimeException;

class GitHubWebhookService
{
    public function verify(Request $request): void
    {
        $secret = (string) config("integrations.github.webhook_secret");
        $signature = (string) $request->header("X-Hub-Signature-256");
        $payload = $request->getContent();

        if (!$secret) {
            throw new RuntimeException("GitHub webhook secret is missing.");
        }

        if (!$signature) {
            throw new RuntimeException("Missing X-Hub-Signature-256 header.");
        }

        $expected = "sha256=" . hash_hmac("sha256", $payload, $secret);

        if (!hash_equals($expected, $signature)) {
            throw new RuntimeException("Invalid GitHub webhook signature.");
        }
    }

    public function event(Request $request): string
    {
        return (string) $request->header("X-GitHub-Event", "");
    }

    public function deliveryId(Request $request): string
    {
        return (string) $request->header("X-GitHub-Delivery", "");
    }
}