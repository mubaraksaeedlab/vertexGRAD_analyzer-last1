<?php

namespace App\Modules\Languages\Analyzers;

use App\Modules\Languages\Contracts\LanguageAnalyzerInterface;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectFile;

class PythonAnalyzer extends BaseAnalyzer implements LanguageAnalyzerInterface
{
    public function language(): string
    {
        return 'python';
    }

    public function supportedExtensions(): array
    {
        return ['py'];
    }

    public function canHandle(ProjectFile $file): bool
    {
        return strtolower((string) $file->extension) === 'py';
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
            $issues = array_merge($issues, $this->detectOsSystemUsage($file, $lines));
            $issues = array_merge($issues, $this->detectSubprocessUsage($file, $lines));
            $issues = array_merge($issues, $this->detectPickleUsage($file, $lines));
            $issues = array_merge($issues, $this->detectYamlLoadUsage($file, $lines));
            $issues = array_merge($issues, $this->detectHardcodedSecrets($file, $lines));
        }

        return $issues;
    }

    protected function detectEvalUsage(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            file: $file,
            lines: $lines,
            pattern: '/\beval\s*\(/i',
            ruleCode: 'PY_EVAL_USAGE',
            category: 'security',
            severity: 'critical',
            title: 'Use of eval() detected',
            description: 'eval() executes dynamic Python expressions and may introduce code injection risks.',
            recommendation: 'Avoid eval() and replace it with explicit, safe logic.',
            language: 'python',
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
            ruleCode: 'PY_EXEC_USAGE',
            category: 'security',
            severity: 'critical',
            title: 'Use of exec() detected',
            description: 'exec() executes dynamic Python code and can enable arbitrary code execution.',
            recommendation: 'Avoid exec() unless absolutely necessary and replace it with safer structured logic.',
            language: 'python',
            confidence: 99,
            extraMetadata: ['risk' => 'code_execution']
        );
    }

    protected function detectOsSystemUsage(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            file: $file,
            lines: $lines,
            pattern: '/\bos\.system\s*\(/i',
            ruleCode: 'PY_OS_SYSTEM_USAGE',
            category: 'security',
            severity: 'high',
            title: 'Use of os.system() detected',
            description: 'os.system() executes shell commands and may expose the application to command injection.',
            recommendation: 'Avoid os.system() or strictly sanitize and validate all command input.',
            language: 'python',
            confidence: 97,
            extraMetadata: ['risk' => 'command_execution']
        );
    }

    protected function detectSubprocessUsage(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            file: $file,
            lines: $lines,
            pattern: '/\bsubprocess\.(Popen|call|run)\s*\(/i',
            ruleCode: 'PY_SUBPROCESS_USAGE',
            category: 'security',
            severity: 'high',
            title: 'Subprocess execution detected',
            description: 'subprocess execution may run external commands and introduce command execution or injection risks.',
            recommendation: 'Validate all arguments carefully and avoid unsafe subprocess usage with untrusted input.',
            language: 'python',
            confidence: 95,
            extraMetadata: ['risk' => 'command_execution']
        );
    }

    protected function detectPickleUsage(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            file: $file,
            lines: $lines,
            pattern: '/\bpickle\.loads\s*\(/i',
            ruleCode: 'PY_PICKLE_USAGE',
            category: 'security',
            severity: 'critical',
            title: 'pickle.loads() detected',
            description: 'pickle.loads() on untrusted data may lead to arbitrary code execution.',
            recommendation: 'Avoid deserializing untrusted pickle data and prefer safer formats such as JSON.',
            language: 'python',
            confidence: 99,
            extraMetadata: ['risk' => 'insecure_deserialization']
        );
    }

    protected function detectYamlLoadUsage(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            file: $file,
            lines: $lines,
            pattern: '/\byaml\.load\s*\(/i',
            ruleCode: 'PY_YAML_LOAD_USAGE',
            category: 'security',
            severity: 'high',
            title: 'yaml.load() detected',
            description: 'yaml.load() may perform unsafe deserialization depending on the loader and input source.',
            recommendation: 'Use yaml.safe_load() when handling YAML from untrusted or external sources.',
            language: 'python',
            confidence: 93,
            extraMetadata: ['risk' => 'unsafe_deserialization']
        );
    }

    protected function detectHardcodedSecrets(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            file: $file,
            lines: $lines,
            pattern: '/(api[_\-]?key|token|secret|password)\s*[:=]\s*[\'"][^\'"]+[\'"]/i',
            ruleCode: 'PY_SECRET',
            category: 'security',
            severity: 'high',
            title: 'Hardcoded secret detected',
            description: 'Sensitive credentials appear directly in source code, increasing the risk of secret leakage.',
            recommendation: 'Move secrets to environment variables or a secure secret management solution.',
            language: 'python',
            confidence: 97,
            extraMetadata: ['risk' => 'secret_exposure']
        );
    }
}