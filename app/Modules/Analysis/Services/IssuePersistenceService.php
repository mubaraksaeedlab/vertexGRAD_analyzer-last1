<?php

namespace App\Modules\Analysis\Services;

use App\Modules\Analysis\Models\AnalysisRun;
use App\Modules\Analysis\Models\Issue;
use App\Modules\Projects\Models\Project;
use Illuminate\Support\Str;

class IssuePersistenceService
{
    public function store(Project $project, AnalysisRun $analysisRun, array $issues): void
    {
        Issue::where('analysis_run_id', $analysisRun->id)->delete();

        foreach ($issues as $issue) {
            if (!is_array($issue)) {
                continue;
            }

            $snippet = $this->sanitizeSnippet($issue['snippet'] ?? null);
            $metadata = $this->sanitizeMetadata($issue['metadata'] ?? null);

            $sanitizedRuleCode = $this->sanitizeRuleCode($issue['rule_code'] ?? null);
            $sanitizedCategory = $this->sanitizeCategory($issue['category'] ?? null);
            $sanitizedSeverity = $this->sanitizeSeverity($issue['severity'] ?? null);
            $sanitizedTitle = $this->sanitizeTitle($issue['title'] ?? null);

            $fingerprintData = app(IssueFingerprintService::class)->make(
                $sanitizedRuleCode,
                $sanitizedCategory,
                $sanitizedSeverity,
                $issue['file_path'] ?? null,
                $snippet,
                $sanitizedTitle
            );

            Issue::create([
                'project_id'           => $project->id,
                'analysis_run_id'      => $analysisRun->id,
                'project_file_id'      => $issue['project_file_id'] ?? null,
                'rule_code'            => $sanitizedRuleCode,
                'category'             => $sanitizedCategory,
                'severity'             => $sanitizedSeverity,
                'language'             => $this->sanitizeLanguage($issue['language'] ?? null),
                'title'                => $sanitizedTitle,
                'description'          => $this->sanitizeText($issue['description'] ?? null, 2000),
                'recommendation'       => $this->sanitizeText($issue['recommendation'] ?? null, 2000),
                'line_start'           => $this->sanitizeNullableInt($issue['line_start'] ?? null),
                'line_end'             => $this->sanitizeNullableInt($issue['line_end'] ?? null),
                'column_start'         => $this->sanitizeNullableInt($issue['column_start'] ?? null),
                'column_end'           => $this->sanitizeNullableInt($issue['column_end'] ?? null),
                'snippet'              => $snippet,
                'fingerprint'          => $fingerprintData['fingerprint'],
                'normalized_snippet'   => $fingerprintData['normalized_snippet'],
                'fingerprint_version'  => $fingerprintData['fingerprint_version'],
                'confidence'           => $this->sanitizeConfidence($issue['confidence'] ?? null),
                'is_resolved'          => false,
                'resolved_at'          => null,
                'metadata'             => $metadata,
            ]);
        }
    }

    protected function sanitizeSnippet(?string $snippet): ?string
    {
        if (!$snippet) {
            return null;
        }

        $snippet = trim($snippet);

        if ($snippet === '') {
            return null;
        }

        return Str::limit($snippet, 1000, '...');
    }

    protected function sanitizeMetadata(mixed $metadata): ?array
    {
        if (!is_array($metadata)) {
            return null;
        }

        array_walk_recursive($metadata, function (&$value) {
            if (is_string($value)) {
                $value = Str::limit(trim($value), 500, '...');
            }
        });

        return $metadata;
    }

    protected function sanitizeRuleCode(?string $ruleCode): string
    {
        $ruleCode = strtoupper(trim((string) $ruleCode));

        return $ruleCode !== '' ? $ruleCode : 'UNKNOWN_RULE';
    }

    protected function sanitizeCategory(?string $category): string
    {
        $category = strtolower(trim((string) $category));

        $allowed = ['security', 'quality', 'performance', 'structure', 'maintainability'];

        return in_array($category, $allowed, true) ? $category : 'quality';
    }

    protected function sanitizeSeverity(?string $severity): string
    {
        $severity = strtolower(trim((string) $severity));

        $allowed = ['critical', 'high', 'medium', 'low', 'info'];

        return in_array($severity, $allowed, true) ? $severity : 'low';
    }

    protected function sanitizeLanguage(?string $language): ?string
    {
        $language = strtolower(trim((string) $language));

        return $language !== '' ? $language : null;
    }

    protected function sanitizeTitle(?string $title): string
    {
        $title = trim((string) $title);

        if ($title === '') {
            return 'Untitled Issue';
        }

        return Str::limit($title, 255, '...');
    }

    protected function sanitizeText(?string $text, int $limit = 2000): ?string
    {
        if ($text === null) {
            return null;
        }

        $text = trim($text);

        if ($text === '') {
            return null;
        }

        return Str::limit($text, $limit, '...');
    }

    protected function sanitizeNullableInt(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        $value = (int) $value;

        return $value >= 0 ? $value : null;
    }

    protected function sanitizeConfidence(mixed $confidence): int
    {
        if (!is_numeric($confidence)) {
            return 90;
        }

        $confidence = (int) $confidence;

        if ($confidence < 0) {
            return 0;
        }

        if ($confidence > 100) {
            return 100;
        }

        return $confidence;
    }
}