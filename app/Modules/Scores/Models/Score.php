<?php

namespace App\Modules\Scores\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Projects\Models\Project;
use App\Modules\Analysis\Models\AnalysisRun;

class Score extends Model
{
    protected $table = 'scores';

    protected $fillable = [
        'project_id',
        'analysis_run_id',
        'overall_score',
        'security_score',
        'quality_score',
        'performance_score',
        'structure_score',
        'maintainability_score',
        'issues_count',
        'critical_count',
        'high_count',
        'medium_count',
        'low_count',
        'info_count',
        'grade',
        'breakdown',
        'metadata',
    ];

    protected $casts = [
        'overall_score' => 'decimal:2',
        'security_score' => 'decimal:2',
        'quality_score' => 'decimal:2',
        'performance_score' => 'decimal:2',
        'structure_score' => 'decimal:2',
        'maintainability_score' => 'decimal:2',
        'issues_count' => 'integer',
        'critical_count' => 'integer',
        'high_count' => 'integer',
        'medium_count' => 'integer',
        'low_count' => 'integer',
        'info_count' => 'integer',
        'breakdown' => 'array',
        'metadata' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function analysisRun(): BelongsTo
    {
        return $this->belongsTo(AnalysisRun::class);
    }
}