<?php

namespace App\Modules\Languages\Analyzers;

use App\Modules\Languages\Contracts\LanguageAnalyzerInterface;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectFile;

class JavaAnalyzer extends BaseAnalyzer implements LanguageAnalyzerInterface
{
    public function language(): string
    {
        return 'java';
    }

    public function supportedExtensions(): array
    {
        return ['java'];
    }

    public function canHandle(ProjectFile $file): bool
    {
        return strtolower((string) $file->extension) === 'java';
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

            $issues = array_merge($issues, $this->detectRuntimeExec($file, $lines));
            $issues = array_merge($issues, $this->detectProcessBuilder($file, $lines));
            $issues = array_merge($issues, $this->detectPrintStackTrace($file, $lines));
            $issues = array_merge($issues, $this->detectSqlConcatenation($file, $lines));
            $issues = array_merge($issues, $this->detectHardcodedSecrets($file, $lines));
        }

        return $issues;
    }

    protected function detectRuntimeExec(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/Runtime\s*\.\s*getRuntime\s*\(\s*\)\s*\.\s*exec\s*\(/i',
            'JAVA_RUNTIME_EXEC',
            'security',
            'high',
            'Use of Runtime.exec() detected',
            'Runtime.exec() may execute system commands and expose the application to command execution risks.',
            'Avoid Runtime.exec() when possible, and strictly validate all command input.',
            'java',
            96,
            ['risk' => 'command_execution']
        );
    }

    protected function detectProcessBuilder(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/new\s+ProcessBuilder\s*\(/i',
            'JAVA_PROCESS_BUILDER_USAGE',
            'security',
            'high',
            'Use of ProcessBuilder detected',
            'ProcessBuilder may launch external processes and introduce unsafe command construction.',
            'Review command construction carefully and sanitize all external input.',
            'java',
            94,
            ['risk' => 'process_execution']
        );
    }

    protected function detectPrintStackTrace(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/\bprintStackTrace\s*\(/i',
            'JAVA_PRINT_STACKTRACE_USAGE',
            'maintainability',
            'medium',
            'Use of printStackTrace() detected',
            'Printing stack traces directly may expose internal implementation details and weakens production-grade error handling.',
            'Use structured logging and centralized exception handling instead of direct stack trace printing.',
            'java',
            88,
            ['risk' => 'information_exposure']
        );
    }

    protected function detectSqlConcatenation(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/(SELECT|INSERT|UPDATE|DELETE).*\+/i',
            'JAVA_SQL_STRING_CONCAT',
            'security',
            'high',
            'Possible SQL string concatenation detected',
            'Building SQL queries with string concatenation may lead to SQL injection vulnerabilities.',
            'Use prepared statements, parameterized queries, or framework-safe data access layers.',
            'java',
            93,
            ['risk' => 'sql_injection']
        );
    }

    protected function detectHardcodedSecrets(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/(api[_\-]?key|token|secret|password)\s*=\s*"[^"]+"/i',
            'JAVA_HARDCODED_SECRET',
            'security',
            'high',
            'Hardcoded secret detected',
            'Sensitive credentials appear directly in source code, increasing the risk of secret leakage.',
            'Move secrets to environment variables, secure configuration, or secret management services.',
            'java',
            97,
            ['risk' => 'secret_exposure']
        );
    }
}