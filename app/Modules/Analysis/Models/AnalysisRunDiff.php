<?php

namespace App\Modules\Analysis\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalysisRunDiff extends Model
{
    protected $table = 'analysis_run_diffs';

    protected $fillable = [
        'project_id',
        'old_run_id',
        'new_run_id',
        'old_total_issues',
        'new_total_issues',
        'resolved_count',
        'existing_count',
        'new_count',
        'resolved_rules',
        'existing_rules',
        'new_rules',
        'comparison_scope',
    ];

    protected $casts = [
        'resolved_rules' => 'array',
        'existing_rules' => 'array',
        'new_rules' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Projects\Models\Project::class);
    }

    public function oldRun(): BelongsTo
    {
        return $this->belongsTo(AnalysisRun::class, 'old_run_id');
    }

    public function newRun(): BelongsTo
    {
        return $this->belongsTo(AnalysisRun::class, 'new_run_id');
    }
}