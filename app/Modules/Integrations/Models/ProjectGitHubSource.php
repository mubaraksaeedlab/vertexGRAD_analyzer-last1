<?php

namespace App\Modules\Integrations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Projects\Models\Project;

class ProjectGitHubSource extends Model
{
    protected $table = 'project_github_sources';

    protected $fillable = [
        'project_id',
        'github_repository_id',
        'branch',
        'path_prefix',
        'last_commit_sha',
        'auto_sync',
        'analyze_on_push',
        'analyze_on_pull_request',
        'created_by_user_id',
    ];

    protected $casts = [
        'auto_sync' => 'boolean',
        'analyze_on_push' => 'boolean',
        'analyze_on_pull_request' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function repository(): BelongsTo
    {
        return $this->belongsTo(GitHubRepository::class, 'github_repository_id');
    }
}