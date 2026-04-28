<?php

namespace App\Modules\Languages\Analyzers;

use App\Modules\Languages\Contracts\LanguageAnalyzerInterface;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectFile;

class GoAnalyzer extends BaseAnalyzer implements LanguageAnalyzerInterface
{
    public function language(): string
    {
        return 'go';
    }

    public function supportedExtensions(): array
    {
        return ['go'];
    }

    public function canHandle(ProjectFile $file): bool
    {
        return strtolower((string) $file->extension) === 'go';
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

            $issues = array_merge($issues, $this->detectCommandExec($file, $lines));
            $issues = array_merge($issues, $this->detectSqlQueryConcat($file, $lines));
            $issues = array_merge($issues, $this->detectPanicUsage($file, $lines));
            $issues = array_merge($issues, $this->detectInsecureSkipVerify($file, $lines));
            $issues = array_merge($issues, $this->detectHardcodedSecrets($file, $lines));
        }

        return $issues;
    }

    protected function detectCommandExec(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/exec\s*\.\s*Command\s*\(/i',
            'GO_EXEC_COMMAND_USAGE',
            'security',
            'high',
            'Use of exec.Command() detected',
            'exec.Command() may run external programs or system commands and can introduce command execution risks.',
            'Validate all command arguments strictly and avoid passing untrusted input into external commands.',
            'go',
            96,
            ['risk' => 'command_execution']
        );
    }

    protected function detectSqlQueryConcat(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/(SELECT|INSERT|UPDATE|DELETE).*\+/i',
            'GO_SQL_STRING_CONCAT',
            'security',
            'high',
            'Possible SQL string concatenation detected',
            'Building SQL queries with string concatenation may lead to SQL injection vulnerabilities.',
            'Use prepared statements, placeholders, or safe query builders instead of raw concatenation.',
            'go',
            93,
            ['risk' => 'sql_injection']
        );
    }

    protected function detectPanicUsage(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/\bpanic\s*\(/i',
            'GO_PANIC_USAGE',
            'maintainability',
            'medium',
            'Use of panic() detected',
            'panic() may abruptly terminate execution and can reduce application stability and maintainability.',
            'Prefer explicit error handling and reserve panic() for truly unrecoverable conditions.',
            'go',
            85,
            ['risk' => 'runtime_instability']
        );
    }

    protected function detectInsecureSkipVerify(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/InsecureSkipVerify\s*:\s*true/i',
            'GO_INSECURE_SKIP_VERIFY',
            'security',
            'critical',
            'TLS certificate verification disabled',
            'Disabling TLS certificate verification weakens transport security and may expose connections to man-in-the-middle attacks.',
            'Enable certificate verification and avoid InsecureSkipVerify in production environments.',
            'go',
            99,
            ['risk' => 'tls_misconfiguration']
        );
    }

    protected function detectHardcodedSecrets(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/(api[_\-]?key|token|secret|password)\s*[:=]\s*"[^"]+"/i',
            'GO_HARDCODED_SECRET',
            'security',
            'high',
            'Hardcoded secret detected',
            'Sensitive data appears directly in source code, increasing the risk of credential leakage.',
            'Move secrets to environment variables or a secure secret management solution.',
            'go',
            97,
            ['risk' => 'secret_exposure']
        );
    }
}