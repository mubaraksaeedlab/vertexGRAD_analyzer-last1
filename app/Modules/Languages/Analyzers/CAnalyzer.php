<?php

namespace App\Modules\Languages\Analyzers;

use App\Modules\Languages\Contracts\LanguageAnalyzerInterface;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectFile;

class CAnalyzer extends BaseAnalyzer implements LanguageAnalyzerInterface
{
    public function language(): string
    {
        return 'c';
    }

    public function supportedExtensions(): array
    {
        return ['c', 'h'];
    }

    public function canHandle(ProjectFile $file): bool
    {
        return in_array(strtolower((string) $file->extension), ['c', 'h'], true);
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

            $issues = array_merge($issues, $this->detectGets($file, $lines));
            $issues = array_merge($issues, $this->detectStrcpy($file, $lines));
            $issues = array_merge($issues, $this->detectSprintf($file, $lines));
            $issues = array_merge($issues, $this->detectSystem($file, $lines));
            $issues = array_merge($issues, $this->detectHardcodedSecrets($file, $lines));
        }

        return $issues;
    }

    protected function detectGets(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/\bgets\s*\(/i',
            'C_GETS_USAGE',
            'security',
            'critical',
            'Use of gets() detected',
            'gets() is unsafe and may cause buffer overflow because it does not check input length.',
            'Replace gets() with fgets() and always validate buffer sizes.',
            'c',
            98,
            ['risk' => 'buffer_overflow']
        );
    }

    protected function detectStrcpy(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/\bstrcpy\s*\(/i',
            'C_STRCPY_USAGE',
            'security',
            'high',
            'Use of strcpy() detected',
            'strcpy() may overflow destination buffers when input length is not controlled.',
            'Use strncpy(), snprintf(), or safer bounded alternatives.',
            'c',
            95,
            ['risk' => 'buffer_overflow']
        );
    }

    protected function detectSprintf(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/\bsprintf\s*\(/i',
            'C_SPRINTF_USAGE',
            'maintainability',
            'medium',
            'Use of sprintf() detected',
            'sprintf() may write beyond buffer boundaries and often indicates fragile string handling.',
            'Use snprintf() and enforce explicit output size limits.',
            'c',
            88,
            ['risk' => 'unsafe_string_formatting']
        );
    }

    protected function detectSystem(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/\bsystem\s*\(/i',
            'C_SYSTEM_USAGE',
            'security',
            'high',
            'Use of system() detected',
            'system() executes shell commands and may expose the application to command injection.',
            'Avoid system() when possible, or strictly validate and sanitize all command inputs.',
            'c',
            96,
            ['risk' => 'command_execution']
        );
    }

    protected function detectHardcodedSecrets(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/(api[_\-]?key|token|secret|password)\s*=\s*"[^"]+"/i',
            'C_HARDCODED_SECRET',
            'security',
            'high',
            'Hardcoded secret detected',
            'Sensitive credentials appear directly in source code, which increases exposure risk.',
            'Move secrets to secure configuration or environment-based secret storage.',
            'c',
            97,
            ['risk' => 'secret_exposure']
        );
    }
}