<?php

namespace App\Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Analysis\Models\AnalysisRun;
use App\Modules\Analysis\Models\Issue;
use App\Modules\Scores\Models\Score;
use App\Modules\Reports\Models\Report;
use App\Modules\AI\Models\AiConversation;
use App\Modules\AI\Models\AiInsight;
use App\Modules\Integrations\Models\ProjectGitHubSource;

class Project extends Model
{
    use SoftDeletes;

    protected $table = 'projects';

    protected $fillable = [
        'uuid',
        'token',
        'name',
        'slug',
        'source_type',
        'source_name',
        'source_path',
        'archive_path',
        'extracted_path',
        'primary_language',
        'detected_languages',
        'total_files',
        'source_files',
        'total_lines',
        'archive_size',
        'extracted_size',
        'status',
        'scan_status',
        'owner_name',
        'owner_email',
        'platform_project_id',
        'external_reference',
        'external_source',
        'integration_mode',
        'callback_url',
        'metadata',
        'summary',
        'uploaded_at',
        'scanned_at',
        'last_activity_at',
    ];

    protected $casts = [
        'detected_languages' => 'array',
        'metadata' => 'array',
        'summary' => 'array',
        'uploaded_at' => 'datetime',
        'scanned_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function analysisRuns(): HasMany
    {
        return $this->hasMany(AnalysisRun::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function aiInsights(): HasMany
    {
        return $this->hasMany(AiInsight::class);
    }

    public function aiConversations(): HasMany
    {
        return $this->hasMany(AiConversation::class);
    }

    public function githubSources(): HasMany
    {
        return $this->hasMany(ProjectGitHubSource::class);
    }
}