<?php

namespace App\Modules\Languages\Analyzers;

use App\Modules\Languages\Contracts\LanguageAnalyzerInterface;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectFile;

class JavaScriptAnalyzer extends BaseAnalyzer implements LanguageAnalyzerInterface
{
    public function language(): string
    {
        return 'javascript';
    }

    public function supportedExtensions(): array
    {
        return ['js', 'jsx'];
    }

    public function canHandle(ProjectFile $file): bool
    {
        return in_array(strtolower((string) $file->extension), ['js', 'jsx'], true);
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
            $issues = array_merge($issues, $this->detectExec($file, $lines));
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
            'JS_EVAL_USAGE',
            'security',
            'critical',
            'Use of eval() detected',
            'eval() executes dynamic code and may introduce code injection vulnerabilities.',
            'Avoid eval() entirely and replace it with safe, explicit logic.',
            'javascript',
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
            'JS_FUNCTION_CONSTRUCTOR',
            'security',
            'high',
            'Use of Function constructor detected',
            'The Function constructor executes dynamically generated code and can introduce injection risks.',
            'Avoid using Function constructor and replace it with safer static logic.',
            'javascript',
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
            'JS_INNER_HTML_USAGE',
            'security',
            'high',
            'Use of innerHTML detected',
            'innerHTML may expose the application to XSS vulnerabilities if content is not sanitized.',
            'Prefer textContent or sanitize all HTML content before rendering it.',
            'javascript',
            92,
            ['risk' => 'xss']
        );
    }

    protected function detectExec(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/\bexec\s*\(/i',
            'JS_EXEC_USAGE',
            'security',
            'high',
            'Use of exec detected',
            'exec may execute system-level commands or unsafe shell operations depending on the runtime context.',
            'Avoid exec or strictly validate and sanitize all external input.',
            'javascript',
            94,
            ['risk' => 'command_execution']
        );
    }

    protected function detectHardcodedSecrets(ProjectFile $file, array $lines): array
    {
        return $this->scanPattern(
            $file,
            $lines,
            '/(api[_\-]?key|token|secret|password)\s*[:=]\s*[\'"][^\'"]+[\'"]/i',
            'JS_HARDCODED_SECRET',
            'security',
            'high',
            'Hardcoded secret detected',
            'Sensitive credentials appear directly in source code, which increases the risk of leakage.',
            'Move secrets to environment variables or a secure secret management solution.',
            'javascript',
            97,
            ['risk' => 'secret_exposure']
        );
    }
}