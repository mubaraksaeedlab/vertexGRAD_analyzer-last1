<?php

namespace App\Modules\Analysis\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectFile;
use App\Modules\AI\Models\AiIssueInsight;

class Issue extends Model
{
    protected $table = 'issues';

    protected $fillable = [
        'project_id',
        'analysis_run_id',
        'project_file_id',
        'rule_code',
        'category',
        'severity',
        'language',
        'title',
        'description',
        'recommendation',
        'line_start',
        'line_end',
        'column_start',
        'column_end',
        'snippet',
        'confidence',
        'is_resolved',
        'resolved_at',
        'metadata',

        // ✅ أضف هذه الثلاثة
        'fingerprint',
        'normalized_snippet',
        'fingerprint_version',
    ];

    protected $casts = [
        'confidence' => 'decimal:2',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
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

    public function projectFile(): BelongsTo
    {
        return $this->belongsTo(ProjectFile::class);
    }

    // =========================
    // AI RELATION
    // =========================

    public function aiInsight(): HasOne
    {
        return $this->hasOne(AiIssueInsight::class);
    }
}