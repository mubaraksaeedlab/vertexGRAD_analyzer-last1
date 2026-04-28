<?php

namespace App\Modules\Integrations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GitHubRepository extends Model
{
    protected $table = 'github_repositories';

    protected $fillable = [
        'github_repository_id',
        'github_installation_id',
        'full_name',
        'owner',
        'name',
        'is_private',
        'default_branch',
        'language',
        'html_url',
        'clone_url',
        'last_pushed_at',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'last_pushed_at' => 'datetime',
    ];

    public function installation(): BelongsTo
    {
        return $this->belongsTo(GitHubInstallation::class, 'github_installation_id', 'github_installation_id');
    }

    public function projectSources(): HasMany
    {
        return $this->hasMany(ProjectGitHubSource::class, 'github_repository_id');
    }
}