<?php

namespace App\Modules\Integrations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GitHubInstallation extends Model
{
    protected $table = 'github_installations';

    protected $fillable = [
        'github_installation_id',
        'github_app_id',
        'account_type',
        'account_id',
        'account_login',
        'account_avatar_url',
        'target_type',
        'target_id',
        'installed_by_user_id',
        'permissions',
        'events',
        'suspended_at',
        'last_synced_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'events' => 'array',
        'suspended_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function repositories(): HasMany
    {
        return $this->hasMany(GitHubRepository::class, 'github_installation_id', 'github_installation_id');
    }
}