<?php

namespace App\Modules\Languages\Analyzers;

use App\Modules\Projects\Models\ProjectFile;

abstract class BaseAnalyzer
{
    protected function scanPattern(
        ProjectFile $file,
        array $lines,
        string $pattern,
        string $ruleCode,
        string $category,
        string $severity,
        string $title,
        string $description,
        string $recommendation,
        string $language,
        int $confidence = 90,
        array $extraMetadata = []
    ): array {
        $issues = [];

        foreach ($lines as $index => $line) {
            if (!is_string($line)) {
                continue;
            }

            $originalLine = $line;
            $trimmedLine = trim($originalLine);

            if ($trimmedLine === '') {
                continue;
            }

            $matched = @preg_match($pattern, $originalLine, $matches, PREG_OFFSET_CAPTURE);

            if ($matched !== 1) {
                continue;
            }

            $columnStart = null;
            $columnEnd = null;

            if (!empty($matches[0]) && isset($matches[0][1]) && is_int($matches[0][1])) {
                $columnStart = $matches[0][1] + 1;
                $columnEnd = $columnStart + strlen((string) $matches[0][0]) - 1;
            }

            $issues[] = [
                'project_file_id' => $file->id,
                'rule_code' => strtoupper(trim($ruleCode)),
                'category' => strtolower(trim($category)),
                'severity' => strtolower(trim($severity)),
                'language' => strtolower(trim($language)),
                'title' => trim($title),
                'description' => trim($description),
                'recommendation' => trim($recommendation),
                'line_start' => $index + 1,
                'line_end' => $index + 1,
                'column_start' => $columnStart,
                'column_end' => $columnEnd,
                'snippet' => $trimmedLine,
                'confidence' => max(0, min(100, $confidence)),
                'metadata' => array_merge([
                    'file_name' => $file->file_name,
                    'relative_path' => $file->relative_path,
                    'extension' => $file->extension,
                    'matched_pattern' => $pattern,
                ], $extraMetadata),
            ];
        }

        return $issues;
    }
}