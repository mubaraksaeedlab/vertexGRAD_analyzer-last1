<?php

namespace App\Modules\Languages\Analyzers;

use App\Modules\Languages\Contracts\LanguageAnalyzerInterface;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectFile;

class CSharpAnalyzer extends BaseAnalyzer implements LanguageAnalyzerInterface
{
    public function language(): string
    {
        return 'csharp';
    }

    public function supportedExtensions(): array
    {
        return ['cs'];
    }

    public function canHandle(ProjectFile $file): bool
    {
        return strtolower((string) $file->extension) === 'cs';
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

            $issues = array_merge($issues, $this->detectProcessStart($file, $lines));
            $issues = array_merge($issues, $this->detectBinaryFormatter($file, $lines));
            $issues = array_merge($issues, $this->detectSqlConcatenation($file, $lines));
            $issues = array_merge($issues, $this->detectConsoleWriteLineException($file, $lines));
            $issues = array_merge($issues, $this->detectHardcodedSecrets($file, $lines));
        }

        return $issues;
    }

    protected function detectProcessStart(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/Process\s*\.\s*Start\s*\(/i',
            'CSHARP_PROCESS_START_USAGE',
            'security',
            'high',
            'Use of Process.Start() detected',
            'Process.Start() may execute external programs or system commands, which can introduce command execution risks.',
            'Avoid Process.Start() when possible, and strictly validate all command arguments and external input.',
            'csharp',
            96,
            ['risk' => 'command_execution']
        );
    }

    protected function detectBinaryFormatter(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/BinaryFormatter/i',
            'CSHARP_BINARYFORMATTER_USAGE',
            'security',
            'critical',
            'BinaryFormatter usage detected',
            'BinaryFormatter is unsafe for untrusted input and may lead to insecure deserialization vulnerabilities.',
            'Replace BinaryFormatter with safer serializers such as System.Text.Json or other secure alternatives.',
            'csharp',
            99,
            ['risk' => 'insecure_deserialization']
        );
    }

    protected function detectSqlConcatenation(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/(SELECT|INSERT|UPDATE|DELETE).*\+/i',
            'CSHARP_SQL_STRING_CONCAT',
            'security',
            'high',
            'Possible SQL string concatenation detected',
            'Building SQL queries with string concatenation may expose the application to SQL injection.',
            'Use parameterized queries, prepared statements, or ORM-safe query builders.',
            'csharp',
            93,
            ['risk' => 'sql_injection']
        );
    }

    protected function detectConsoleWriteLineException(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/Console\s*\.\s*WriteLine\s*\(\s*.*Exception/i',
            'CSHARP_CONSOLE_EXCEPTION_OUTPUT',
            'maintainability',
            'medium',
            'Possible exception details printed to console',
            'Printing raw exception details may expose internal implementation details and reduce production-grade error handling quality.',
            'Use structured logging and avoid exposing full exception details directly in production output.',
            'csharp',
            84,
            ['risk' => 'information_exposure']
        );
    }

    protected function detectHardcodedSecrets(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/(api[_\-]?key|token|secret|password)\s*=\s*"[^"]+"/i',
            'CSHARP_HARDCODED_SECRET',
            'security',
            'high',
            'Hardcoded secret detected',
            'Sensitive credentials appear directly in source code, increasing the risk of secret leakage.',
            'Move secrets to secure configuration, secret managers, or environment-based storage.',
            'csharp',
            97,
            ['risk' => 'secret_exposure']
        );
    }
}