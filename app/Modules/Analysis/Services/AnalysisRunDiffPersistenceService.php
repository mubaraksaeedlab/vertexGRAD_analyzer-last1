<?php

namespace App\Modules\Analysis\Services;

use App\Modules\Analysis\Models\AnalysisRunDiff;

class AnalysisRunDiffPersistenceService
{
    public function store(array $data): AnalysisRunDiff
    {
        return AnalysisRunDiff::updateOrCreate(
            [
                'old_run_id' => $data['old_run_id'],
                'new_run_id' => $data['new_run_id'],
            ],
            [
                'project_id' => $data['project_id'],
                'old_total_issues' => $data['old_total_issues'],
                'new_total_issues' => $data['new_total_issues'],
                'resolved_count' => $data['resolved_count'],
                'existing_count' => $data['existing_count'],
                'new_count' => $data['new_count'],
                'resolved_rules' => $data['resolved_rules'],
                'existing_rules' => $data['existing_rules'],
                'new_rules' => $data['new_rules'],
                'comparison_scope' => $data['comparison_scope'] ?? 'rule_code',
            ]
        );
    }
}