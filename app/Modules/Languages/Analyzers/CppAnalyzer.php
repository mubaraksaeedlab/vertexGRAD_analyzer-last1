<?php

namespace App\Modules\Languages\Analyzers;

use App\Modules\Languages\Contracts\LanguageAnalyzerInterface;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectFile;

class CppAnalyzer extends BaseAnalyzer implements LanguageAnalyzerInterface
{
    public function language(): string
    {
        return 'cpp';
    }

    public function supportedExtensions(): array
    {
        return ['cpp', 'cc', 'cxx', 'hpp', 'hh', 'hxx'];
    }

    public function canHandle(ProjectFile $file): bool
    {
        return in_array(strtolower((string) $file->extension), ['cpp', 'cc', 'cxx', 'hpp', 'hh', 'hxx'], true);
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

            $issues = array_merge($issues, $this->detectStrcpy($file, $lines));
            $issues = array_merge($issues, $this->detectSprintf($file, $lines));
            $issues = array_merge($issues, $this->detectSystem($file, $lines));
            $issues = array_merge($issues, $this->detectDeleteMismatch($file, $lines));
            $issues = array_merge($issues, $this->detectHardcodedSecrets($file, $lines));
        }

        return $issues;
    }

    protected function detectStrcpy(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/\bstrcpy\s*\(/i',
            'CPP_STRCPY_USAGE',
            'security',
            'high',
            'Use of strcpy() detected',
            'strcpy() may overflow destination buffers and introduce memory safety risks.',
            'Use strncpy(), std::string, or safer bounded alternatives.',
            'cpp',
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
            'CPP_SPRINTF_USAGE',
            'maintainability',
            'medium',
            'Use of sprintf() detected',
            'sprintf() may write beyond buffer boundaries and often indicates fragile formatting logic.',
            'Use snprintf() or safer formatted output handling.',
            'cpp',
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
            'CPP_SYSTEM_USAGE',
            'security',
            'high',
            'Use of system() detected',
            'system() executes shell commands and may expose the application to command injection.',
            'Avoid system() when possible, or strictly validate and sanitize all command inputs.',
            'cpp',
            96,
            ['risk' => 'command_execution']
        );
    }

    protected function detectDeleteMismatch(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/\bdelete\s+[A-Za-z_][A-Za-z0-9_]*\s*;/i',
            'CPP_DELETE_MISMATCH',
            'structure',
            'medium',
            'Possible delete/delete[] mismatch detected',
            'Deleting arrays using delete instead of delete[] may cause undefined behavior and memory management defects.',
            'Use delete[] when deleting arrays allocated with new[].',
            'cpp',
            84,
            ['risk' => 'memory_management']
        );
    }

    protected function detectHardcodedSecrets(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/(api[_\-]?key|token|secret|password)\s*=\s*"[^"]+"/i',
            'CPP_HARDCODED_SECRET',
            'security',
            'high',
            'Hardcoded secret detected',
            'Sensitive credentials appear directly in source code, increasing exposure risk.',
            'Move secrets to secure configuration or environment-based secret storage.',
            'cpp',
            97,
            ['risk' => 'secret_exposure']
        );
    }
}