<?php

namespace App\Modules\Languages\Analyzers;

use App\Modules\Languages\Contracts\LanguageAnalyzerInterface;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectFile;

class TypeScriptAnalyzer extends BaseAnalyzer implements LanguageAnalyzerInterface
{
    public function language(): string
    {
        return 'typescript';
    }

    public function supportedExtensions(): array
    {
        return ['ts', 'tsx'];
    }

    public function canHandle(ProjectFile $file): bool
    {
        return in_array(strtolower((string) $file->extension), ['ts', 'tsx'], true);
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

            $issues = array_merge($issues, $this->detectEval($file, $lines));
            $issues = array_merge($issues, $this->detectFunctionConstructor($file, $lines));
            $issues = array_merge($issues, $this->detectInnerHTML($file, $lines));
            $issues = array_merge($issues, $this->detectAnyTypeUsage($file, $lines));
            $issues = array_merge($issues, $this->detectHardcodedSecrets($file, $lines));
        }

        return $issues;
    }

    protected function detectEval(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/\beval\s*\(/i',
            'TS_EVAL_USAGE',
            'security',
            'critical',
            'Use of eval() detected',
            'eval() executes dynamic code and may introduce code injection vulnerabilities.',
            'Avoid eval() entirely and replace it with safe, explicit logic.',
            'typescript',
            99,
            ['risk' => 'code_injection']
        );
    }

    protected function detectFunctionConstructor(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/new\s+Function\s*\(/i',
            'TS_FUNCTION_CONSTRUCTOR',
            'security',
            'high',
            'Use of Function constructor detected',
            'The Function constructor executes dynamically generated code and can introduce injection risks.',
            'Avoid using Function constructor and replace it with safer static logic.',
            'typescript',
            96,
            ['risk' => 'dynamic_code_execution']
        );
    }

    protected function detectInnerHTML(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/innerHTML\s*=/i',
            'TS_INNER_HTML_USAGE',
            'security',
            'high',
            'Use of innerHTML detected',
            'innerHTML may expose the application to XSS vulnerabilities if content is not sanitized.',
            'Prefer textContent or sanitize all HTML content before rendering it.',
            'typescript',
            92,
            ['risk' => 'xss']
        );
    }

    protected function detectAnyTypeUsage(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/:\s*any\b/i',
            'TS_ANY_TYPE_USAGE',
            'quality',
            'medium',
            'Use of any type detected',
            'Using any weakens type safety and reduces the reliability of TypeScript checks.',
            'Use explicit types wherever possible to improve safety and maintainability.',
            'typescript',
            91,
            ['risk' => 'weak_type_safety']
        );
    }

    protected function detectHardcodedSecrets(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/(api[_\-]?key|token|secret|password)\s*[:=]\s*[\'"][^\'"]+[\'"]/i',
            'TS_HARDCODED_SECRET',
            'security',
            'high',
            'Hardcoded secret detected',
            'Sensitive credentials appear directly in source code, which increases the risk of leakage.',
            'Move secrets to environment variables or a secure secret management solution.',
            'typescript',
            97,
            ['risk' => 'secret_exposure']
        );
    }
}