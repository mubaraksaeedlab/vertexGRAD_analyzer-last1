<?php

namespace App\Modules\Scores\Services;

use App\Modules\Analysis\Models\AnalysisRun;
use App\Modules\Projects\Models\Project;
use App\Modules\Scores\Models\Score;

class ScoringService
{
    protected array $scoreCategories = [
        'security',
        'quality',
        'performance',
        'structure',
        'maintainability',
    ];

    protected array $categoryWeights = [
        'security' => 25,
        'quality' => 20,
        'performance' => 15,
        'structure' => 15,
        'maintainability' => 25,
    ];

    public function calculate(Project $project, AnalysisRun $analysisRun, array $issues): Score
    {
        $normalizedIssues = $this->normalizeIssues($issues);

        $criticalCount = $this->countBySeverity($normalizedIssues, 'critical');
        $highCount = $this->countBySeverity($normalizedIssues, 'high');
        $mediumCount = $this->countBySeverity($normalizedIssues, 'medium');
        $lowCount = $this->countBySeverity($normalizedIssues, 'low');
        $infoCount = $this->countBySeverity($normalizedIssues, 'info');

        $issuesCount = count($normalizedIssues);

        $securityScore = $this->calculateCategoryScore($normalizedIssues, 'security');
        $qualityScore = $this->calculateCategoryScore($normalizedIssues, 'quality');
        $performanceScore = $this->calculateCategoryScore($normalizedIssues, 'performance');
        $structureScore = $this->calculateCategoryScore($normalizedIssues, 'structure');
        $maintainabilityScore = $this->calculateCategoryScore($normalizedIssues, 'maintainability');

        $componentScores = [
            'security' => $securityScore,
            'quality' => $qualityScore,
            'performance' => $performanceScore,
            'structure' => $structureScore,
            'maintainability' => $maintainabilityScore,
        ];

        $overallScore = $this->calculateOverallScore($componentScores);
        $grade = $this->determineGrade($overallScore);

        return Score::updateOrCreate(
            ['analysis_run_id' => $analysisRun->id],
            [
                'project_id' => $project->id,
                'overall_score' => $overallScore,
                'security_score' => $securityScore,
                'quality_score' => $qualityScore,
                'performance_score' => $performanceScore,
                'structure_score' => $structureScore,
                'maintainability_score' => $maintainabilityScore,
                'issues_count' => $issuesCount,
                'critical_count' => $criticalCount,
                'high_count' => $highCount,
                'medium_count' => $mediumCount,
                'low_count' => $lowCount,
                'info_count' => $infoCount,
                'grade' => $grade,
                'breakdown' => [
                    'weights' => $this->categoryWeights,
                    'severity_distribution' => [
                        'critical' => $criticalCount,
                        'high' => $highCount,
                        'medium' => $mediumCount,
                        'low' => $lowCount,
                        'info' => $infoCount,
                    ],
                    'category_distribution' => $this->buildCategoryDistribution($normalizedIssues),
                    'category_severity_distribution' => $this->buildCategorySeverityDistribution($normalizedIssues),
                    'component_scores' => $componentScores,
                    'total_deduction_by_category' => $this->buildCategoryDeductions($normalizedIssues),
                ],
                'metadata' => [
                    'scoring_version' => '2.1.0',
                    'engine' => 'VertexGrad Analyzer',
                    'scoring_mode' => 'category-aware-confidence-weighted',
                    'risk_level' => $this->determineRiskLevel($overallScore),
                ],
            ]
        );
    }

    protected function normalizeIssues(array $issues): array
    {
        return array_values(array_filter(array_map(function ($issue) {
            if (!is_array($issue)) {
                return null;
            }

            $issue['category'] = $this->normalizeCategory($issue['category'] ?? null);
            $issue['severity'] = $this->normalizeSeverity($issue['severity'] ?? null);
            $issue['confidence'] = $this->normalizeConfidence($issue['confidence'] ?? null);

            return $issue;
        }, $issues)));
    }

    protected function calculateCategoryScore(array $issues, string $category): float
    {
        $deduction = $this->calculateCategoryDeduction($issues, $category);

        return $this->clampScore(100 - $deduction);
    }

    protected function calculateCategoryDeduction(array $issues, string $category): float
    {
        $categoryIssues = array_filter($issues, function ($issue) use ($category) {
            return ($issue['category'] ?? 'quality') === $category;
        });

        if (empty($categoryIssues)) {
            return 0.0;
        }

        $deduction = 0.0;

        foreach ($categoryIssues as $issue) {
            $severityWeight = $this->severityWeight($issue['severity'] ?? 'low');
            $confidenceFactor = $this->confidenceFactor((int) ($issue['confidence'] ?? 90));

            $deduction += $severityWeight * $confidenceFactor;
        }

        return round($deduction, 2);
    }

    protected function calculateOverallScore(array $scores): float
    {
        $weightedTotal = 0.0;
        $totalWeight = 0;

        foreach ($this->categoryWeights as $category => $weight) {
            $score = isset($scores[$category]) ? (float) $scores[$category] : 100.0;

            $weightedTotal += $score * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight <= 0) {
            return 100.0;
        }

        return $this->clampScore($weightedTotal / $totalWeight);
    }

    protected function severityWeight(string $severity): float
    {
        return match ($severity) {
            'critical' => 22.0,
            'high' => 14.0,
            'medium' => 8.0,
            'low' => 3.0,
            'info' => 1.0,
            default => 3.0,
        };
    }

    protected function confidenceFactor(int $confidence): float
    {
        if ($confidence >= 95) {
            return 1.00;
        }

        if ($confidence >= 85) {
            return 0.90;
        }

        if ($confidence >= 70) {
            return 0.75;
        }

        if ($confidence >= 50) {
            return 0.55;
        }

        return 0.35;
    }

    protected function countBySeverity(array $issues, string $severity): int
    {
        return count(array_filter($issues, function ($issue) use ($severity) {
            return strtolower((string) ($issue['severity'] ?? '')) === strtolower($severity);
        }));
    }

    protected function buildCategoryDistribution(array $issues): array
    {
        $distribution = array_fill_keys($this->scoreCategories, 0);

        foreach ($issues as $issue) {
            $category = $issue['category'] ?? 'quality';

            if (array_key_exists($category, $distribution)) {
                $distribution[$category]++;
            }
        }

        return $distribution;
    }

    protected function buildCategorySeverityDistribution(array $issues): array
    {
        $distribution = [];

        foreach ($this->scoreCategories as $category) {
            $distribution[$category] = [
                'critical' => 0,
                'high' => 0,
                'medium' => 0,
                'low' => 0,
                'info' => 0,
            ];
        }

        foreach ($issues as $issue) {
            $category = $issue['category'] ?? 'quality';
            $severity = $issue['severity'] ?? 'low';

            if (
                array_key_exists($category, $distribution) &&
                array_key_exists($severity, $distribution[$category])
            ) {
                $distribution[$category][$severity]++;
            }
        }

        return $distribution;
    }

    protected function buildCategoryDeductions(array $issues): array
    {
        $deductions = [];

        foreach ($this->scoreCategories as $category) {
            $deductions[$category] = $this->calculateCategoryDeduction($issues, $category);
        }

        return $deductions;
    }

    protected function normalizeCategory(?string $category): string
    {
        $category = strtolower(trim((string) $category));

        return match ($category) {
            'security' => 'security',
            'quality' => 'quality',
            'performance' => 'performance',
            'structure' => 'structure',
            'maintainability' => 'maintainability',
            default => 'quality',
        };
    }

    protected function normalizeSeverity(?string $severity): string
    {
        $severity = strtolower(trim((string) $severity));

        return match ($severity) {
            'critical' => 'critical',
            'high' => 'high',
            'medium' => 'medium',
            'low' => 'low',
            'info' => 'info',
            default => 'low',
        };
    }

    protected function normalizeConfidence(mixed $confidence): int
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

    protected function clampScore(float|int $score): float
    {
        return max(0, min(100, round((float) $score, 2)));
    }

    protected function determineGrade(float $score): string
    {
        return match (true) {
            $score >= 97 => 'A+',
            $score >= 93 => 'A',
            $score >= 90 => 'A-',
            $score >= 87 => 'B+',
            $score >= 83 => 'B',
            $score >= 80 => 'B-',
            $score >= 77 => 'C+',
            $score >= 73 => 'C',
            $score >= 70 => 'C-',
            $score >= 67 => 'D+',
            $score >= 63 => 'D',
            $score >= 60 => 'D-',
            default => 'F',
        };
    }

    protected function determineRiskLevel(float $score): string
    {
        return match (true) {
            $score >= 90 => 'low',
            $score >= 75 => 'medium',
            $score >= 60 => 'high',
            default => 'critical',
        };
    }
}