<?php

namespace App\Modules\AI\Models;

use App\Modules\Analysis\Models\AnalysisRun;
use App\Modules\Analysis\Models\Issue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiIssueInsight extends Model
{
    protected $table = 'ai_issue_insights';

    protected $fillable = [
        'issue_id',
        'analysis_run_id',
        'title',
        'explanation',
        'impact',
        'root_cause',
        'fix_suggestion',
        'priority_note',
        'confidence_score',
        'evidence',
        'metadata',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:2',
        'evidence' => 'array',
        'metadata' => 'array',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function analysisRun(): BelongsTo
    {
        return $this->belongsTo(AnalysisRun::class);
    }
}