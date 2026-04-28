<?php

namespace App\Modules\Analysis\Services;

class IssueFingerprintService
{
    public function make(
        ?string $ruleCode,
        ?string $category,
        ?string $severity,
        ?string $filePath,
        ?string $snippet,
        ?string $title = null
    ): array {
        $normalizedSnippet = $this->normalizeSnippet($snippet);
        $normalizedFilePath = $this->normalizePath($filePath);

        $parts = [
            $this->clean($ruleCode),
            $this->clean($category),
            $this->clean($severity),
            $normalizedFilePath,
            $this->clean($title),
            $normalizedSnippet,
        ];

        $source = implode('|', $parts);

        return [
            'fingerprint' => hash('sha256', $source),
            'normalized_snippet' => $normalizedSnippet,
            'fingerprint_version' => 'v1',
            'fingerprint_source' => $source,
        ];
    }

    protected function normalizeSnippet(?string $snippet): string
    {
        if (!$snippet) {
            return '';
        }

        $snippet = str_replace(["\r\n", "\r"], "\n", $snippet);
        $snippet = preg_replace('/\s+/', ' ', $snippet);
        $snippet = trim($snippet);

        return mb_strtolower($snippet);
    }

    protected function normalizePath(?string $path): string
    {
        if (!$path) {
            return '';
        }

        $path = str_replace('\\', '/', trim($path));
        $path = preg_replace('#/+#', '/', $path);

        return mb_strtolower($path);
    }

    protected function clean(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }
}