<?php

namespace App\Modules\Languages\Analyzers;

use App\Modules\Languages\Contracts\LanguageAnalyzerInterface;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectFile;

class DartAnalyzer extends BaseAnalyzer implements LanguageAnalyzerInterface
{
    public function language(): string
    {
        return 'dart';
    }

    public function supportedExtensions(): array
    {
        return ['dart'];
    }

    public function canHandle(ProjectFile $file): bool
    {
        return strtolower((string) $file->extension) === 'dart';
    }

    public function analyze(Project $project, array $files): array
    {
        $issues = [];

        foreach ($files as $file) {
            if (!$file instanceof ProjectFile || !$this->canHandle($file)) {
                continue;
            }

            $path = $file->path;

            if (!is_string($path) || !is_file($path) || !is_readable($path)) {
                continue;
            }

            $content = @file_get_contents($path);

            if ($content === false || trim($content) === '') {
                continue;
            }

            $lines = preg_split("/\r\n|\n|\r/", $content) ?: [];

            $issues = array_merge($issues, $this->detectHttpUsage($file, $lines));
            $issues = array_merge($issues, $this->detectPrintUsage($file, $lines));
            $issues = array_merge($issues, $this->detectDynamicUsage($file, $lines));
            $issues = array_merge($issues, $this->detectHardcodedSecrets($file, $lines));
            $issues = array_merge($issues, $this->detectSetStateInAsyncRisk($file, $lines));
        }

        return $issues;
    }

    protected function detectHttpUsage(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/http\s*\.\s*(get|post|put|delete)\s*\(/i',
            'DART_HTTP_CALL_USAGE',
            'security',
            'medium',
            'HTTP call detected',
            'Outgoing HTTP requests should be reviewed for secure transport, input handling, and endpoint validation.',
            'Validate endpoints, sanitize payload handling, and prefer secure communication practices.',
            'dart',
            82,
            ['risk' => 'network_request_review']
        );
    }

    protected function detectPrintUsage(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/\bprint\s*\(/i',
            'DART_PRINT_USAGE',
            'maintainability',
            'low',
            'Use of print() detected',
            'print() may expose debug information and usually indicates non-production logging practices.',
            'Use structured logging or remove debug output before production deployment.',
            'dart',
            86,
            ['risk' => 'debug_output']
        );
    }

    protected function detectDynamicUsage(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/\bdynamic\b/i',
            'DART_DYNAMIC_USAGE',
            'quality',
            'medium',
            'Use of dynamic detected',
            'Using dynamic weakens type safety and may reduce code clarity and reliability.',
            'Prefer explicit types wherever possible to improve safety and maintainability.',
            'dart',
            90,
            ['risk' => 'weak_type_safety']
        );
    }

    protected function detectHardcodedSecrets(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/(api[_\-]?key|token|secret|password)\s*[:=]\s*[\'"][^\'"]+[\'"]/i',
            'DART_HARDCODED_SECRET',
            'security',
            'high',
            'Hardcoded secret detected',
            'Sensitive data appears directly in code, increasing the risk of credential leakage.',
            'Move secrets to secure runtime configuration, environment variables, or secret management solutions.',
            'dart',
            97,
            ['risk' => 'secret_exposure']
        );
    }

    protected function detectSetStateInAsyncRisk(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/setState\s*\(/i',
            'DART_SETSTATE_USAGE',
            'maintainability',
            'low',
            'Use of setState() detected',
            'Frequent or unsafe setState() usage may lead to fragile widget lifecycle handling and harder state management.',
            'Review widget state management and ensure setState() is used safely around async operations and mounted checks.',
            'dart',
            76,
            ['risk' => 'state_management_fragility']
        );
    }
}