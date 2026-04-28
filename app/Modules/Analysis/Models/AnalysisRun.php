<?php

namespace App\Modules\Analysis\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Modules\Projects\Models\Project;
use App\Modules\Scores\Models\Score;
use App\Modules\Reports\Models\Report;
use App\Modules\AI\Models\AiInsight;
use App\Modules\AI\Models\AiIssueInsight;
use App\Modules\AI\Models\AiConversation;

class AnalysisRun extends Model
{
    protected $table = 'analysis_runs';

    protected $fillable = [
        'project_id',
        'run_uuid',
        'trigger_type',
        'triggered_by_type',
        'triggered_by_id',
        'status',
        'stage',
        'progress_percent',
        'current_step',
        'current_file',
        'total_files',
        'processed_files',
        'analyzer_version',
        'engine_name',
        'issues_found',
        'duration_ms',
        'failure_reason',
        'summary',
        'metrics',
        'context',
        'queued_at',
        'started_at',
        'finished_at',
        'source_type',
'source_reference',
'source_branch',
'source_commit_sha',
'external_event_id',
    ];

    protected $casts = [
        'issues_found' => 'integer',
        'duration_ms' => 'integer',
        'progress_percent' => 'integer',
        'total_files' => 'integer',
        'processed_files' => 'integer',
        'summary' => 'array',
        'metrics' => 'array',
        'context' => 'array',
        'queued_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    public function score(): HasOne
    {
        return $this->hasOne(Score::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(AnalysisRunFile::class);
    }

    // =========================
    // AI RELATIONS
    // =========================

    public function aiInsight(): HasOne
    {
        return $this->hasOne(AiInsight::class);
    }

    public function aiIssueInsights(): HasMany
    {
        return $this->hasMany(AiIssueInsight::class);
    }

    public function aiConversations(): HasMany
    {
        return $this->hasMany(AiConversation::class);
    }
}