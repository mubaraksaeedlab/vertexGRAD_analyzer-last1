<?php

namespace App\Modules\Languages\Analyzers;

use App\Modules\Languages\Contracts\LanguageAnalyzerInterface;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectFile;

class PhpAnalyzer extends BaseAnalyzer implements LanguageAnalyzerInterface
{
    public function language(): string
    {
        return 'php';
    }

    public function supportedExtensions(): array
    {
        return ['php'];
    }

    public function canHandle(ProjectFile $file): bool
    {
        return strtolower((string) $file->extension) === 'php';
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

            $issues = array_merge($issues, $this->detectEvalUsage($file, $lines));
            $issues = array_merge($issues, $this->detectExecUsage($file, $lines));
            $issues = array_merge($issues, $this->detectShellExecUsage($file, $lines));
            $issues = array_merge($issues, $this->detectSystemUsage($file, $lines));
            $issues = array_merge($issues, $this->detectBase64DecodeUsage($file, $lines));
            $issues = array_merge($issues, $this->detectHardcodedSecrets($file, $lines));
            $issues = array_merge($issues, $this->detectRawInputUsage($file, $lines));
        }

        return $issues;
    }

    protected function detectEvalUsage(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            file: $file,
            lines: $lines,
            pattern: '/\beval\s*\(/i',
            ruleCode: 'PHP_EVAL_USAGE',
            category: 'security',
            severity: 'critical',
            title: 'Use of eval() detected',
            description: 'Using eval() can execute dynamic PHP code and introduces severe code injection risks.',
            recommendation: 'Remove eval() and replace it with explicit, safe application logic.',
            language: 'php',
            confidence: 99,
            extraMetadata: ['risk' => 'code_injection']
        );
    }

    protected function detectExecUsage(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            file: $file,
            lines: $lines,
            pattern: '/\bexec\s*\(/i',
            ruleCode: 'PHP_EXEC_USAGE',
            category: 'security',
            severity: 'high',
            title: 'Use of exec() detected',
            description: 'exec() may execute operating system commands and may expose the system to command injection.',
            recommendation: 'Avoid exec() unless strictly necessary and fully validate and sanitize all command input.',
            language: 'php',
            confidence: 97,
            extraMetadata: ['risk' => 'command_execution']
        );
    }

    protected function detectShellExecUsage(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            file: $file,
            lines: $lines,
            pattern: '/\bshell_exec\s*\(/i',
            ruleCode: 'PHP_SHELL_EXEC_USAGE',
            category: 'security',
            severity: 'high',
            title: 'Use of shell_exec() detected',
            description: 'shell_exec() can run shell commands and may expose the application to command injection.',
            recommendation: 'Avoid shell_exec() or tightly control and sanitize every input passed into it.',
            language: 'php',
            confidence: 98,
            extraMetadata: ['risk' => 'command_execution']
        );
    }

    protected function detectSystemUsage(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            file: $file,
            lines: $lines,
            pattern: '/\bsystem\s*\(/i',
            ruleCode: 'PHP_SYSTEM_USAGE',
            category: 'security',
            severity: 'high',
            title: 'Use of system() detected',
            description: 'system() executes system-level commands and can be dangerous when influenced by external input.',
            recommendation: 'Avoid system() or isolate and sanitize command usage very carefully.',
            language: 'php',
            confidence: 97,
            extraMetadata: ['risk' => 'command_execution']
        );
    }

    protected function detectBase64DecodeUsage(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            file: $file,
            lines: $lines,
            pattern: '/\bbase64_decode\s*\(/i',
            ruleCode: 'PHP_BASE64_DECODE_USAGE',
            category: 'maintainability',
            severity: 'medium',
            title: 'Use of base64_decode() detected',
            description: 'base64_decode() is not dangerous by itself, but repeated use may indicate obfuscated, fragile, or suspicious code patterns.',
            recommendation: 'Review whether decoding is necessary and ensure the decoded content is expected and safe.',
            language: 'php',
            confidence: 78,
            extraMetadata: ['risk' => 'possible_obfuscation']
        );
    }

    protected function detectHardcodedSecrets(ProjectFile $file, array $lines): array
    {
        $issues = [];

        $patterns = [
            '/api[_\-]?key\s*=\s*[\'"][^\'"]+[\'"]/i',
            '/secret\s*=\s*[\'"][^\'"]+[\'"]/i',
            '/password\s*=\s*[\'"][^\'"]+[\'"]/i',
            '/token\s*=\s*[\'"][^\'"]+[\'"]/i',
        ];

        foreach ($patterns as $pattern) {
            $issues = array_merge(
                $issues,
                $this->scanPattern(
                    file: $file,
                    lines: $lines,
                    pattern: $pattern,
                    ruleCode: 'PHP_HARDCODED_SECRET',
                    category: 'security',
                    severity: 'high',
                    title: 'Possible hardcoded secret detected',
                    description: 'A possible password, token, secret, or API key appears directly in source code.',
                    recommendation: 'Move secrets to environment variables or a secure secret storage mechanism.',
                    language: 'php',
                    confidence: 97,
                    extraMetadata: ['risk' => 'secret_exposure']
                )
            );
        }

        return $issues;
    }

    protected function detectRawInputUsage(ProjectFile $file, array $lines): array
    {
        $issues = [];

        $patterns = [
            '/\$_GET\[/i',
            '/\$_POST\[/i',
            '/\$_REQUEST\[/i',
        ];

        foreach ($patterns as $pattern) {
            $issues = array_merge(
                $issues,
                $this->scanPattern(
                    file: $file,
                    lines: $lines,
                    pattern: $pattern,
                    ruleCode: 'PHP_RAW_INPUT_USAGE',
                    category: 'quality',
                    severity: 'medium',
                    title: 'Raw user input usage detected',
                    description: 'Direct usage of PHP superglobals may indicate missing validation, sanitization, or input normalization.',
                    recommendation: 'Validate, sanitize, and normalize all incoming user input before using it.',
                    language: 'php',
                    confidence: 86,
                    extraMetadata: ['risk' => 'unsanitized_input']
                )
            );
        }

        return $issues;
    }
}