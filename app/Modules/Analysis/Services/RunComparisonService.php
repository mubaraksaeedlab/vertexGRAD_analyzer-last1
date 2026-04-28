<?php

namespace App\Modules\Analysis\Services;

use App\Modules\Analysis\Models\AnalysisRun;
use App\Modules\Analysis\Models\Issue;
use InvalidArgumentException;

class RunComparisonService
{
    public function compare(int $oldRunId, int $newRunId): array
    {
        $oldRun = AnalysisRun::findOrFail($oldRunId);
        $newRun = AnalysisRun::findOrFail($newRunId);

        if ((int) $oldRun->project_id === (int) $newRun->project_id) {
            return $this->compareByRun($oldRunId, $newRunId, (int) $oldRun->project_id);
        }

        return $this->compareCrossProjectByRuleCode(
            (int) $oldRun->project_id,
            (int) $newRun->project_id,
            $oldRunId,
            $newRunId
        );
    }

    protected function compareByRun(int $oldRunId, int $newRunId, int $projectId): array
    {
        $old = Issue::where('analysis_run_id', $oldRunId)
            ->pluck('rule_code')
            ->toArray();

        $new = Issue::where('analysis_run_id', $newRunId)
            ->pluck('rule_code')
            ->toArray();

        $resolved = array_values(array_diff($old, $new));
        $existing = array_values(array_intersect($old, $new));
        $newRules = array_values(array_diff($new, $old));

        return [
            'project_id' => $projectId,
            'old_run_id' => $oldRunId,
            'new_run_id' => $newRunId,
            'old_total_issues' => count($old),
            'new_total_issues' => count($new),
            'resolved_count' => count($resolved),
            'existing_count' => count($existing),
            'new_count' => count($newRules),
            'resolved_rules' => $resolved,
            'existing_rules' => $existing,
            'new_rules' => $newRules,
            'comparison_scope' => 'rule_code',
        ];
    }

    protected function compareCrossProjectByRuleCode(
        int $oldProjectId,
        int $newProjectId,
        int $oldRunId,
        int $newRunId
    ): array {
        $old = Issue::where('project_id', $oldProjectId)
            ->where('analysis_run_id', $oldRunId)
            ->pluck('rule_code')
            ->toArray();

        $new = Issue::where('project_id', $newProjectId)
            ->where('analysis_run_id', $newRunId)
            ->pluck('rule_code')
            ->toArray();

        $resolved = array_values(array_diff($old, $new));
        $existing = array_values(array_intersect($old, $new));
        $newRules = array_values(array_diff($new, $old));

        return [
            'project_id' => $newProjectId,
            'old_run_id' => $oldRunId,
            'new_run_id' => $newRunId,
            'old_total_issues' => count($old),
            'new_total_issues' => count($new),
            'resolved_count' => count($resolved),
            'existing_count' => count($existing),
            'new_count' => count($newRules),
            'resolved_rules' => $resolved,
            'existing_rules' => $existing,
            'new_rules' => $newRules,
            'comparison_scope' => 'rule_code',
        ];
    }
}