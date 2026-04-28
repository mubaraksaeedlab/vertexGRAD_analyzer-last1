<?php

namespace App\Modules\AI\Models;

use App\Modules\Analysis\Models\AnalysisRun;
use App\Modules\Projects\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiInsight extends Model
{
    protected $table = 'ai_insights';

    protected $fillable = [
        'project_id',
        'analysis_run_id',
        'model_name',
        'model_version',
        'status',
        'maturity_level',
        'overall_health',
        'readiness_score',
        'risk_level',
        'confidence_level',
        'summary',
        'architecture_review',
        'risk_assessment',
        'decision_support',
        'strengths',
        'weaknesses',
        'top_risks',
        'recommendations',
        'metadata',
        'generated_at',
    ];

    protected $casts = [
        'readiness_score' => 'integer',
        'strengths' => 'array',
        'weaknesses' => 'array',
        'top_risks' => 'array',
        'recommendations' => 'array',
        'metadata' => 'array',
        'generated_at' => 'datetime',
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