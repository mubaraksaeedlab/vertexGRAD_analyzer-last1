<?php

namespace App\Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFile extends Model
{
    protected $table = 'project_files';

    protected $fillable = [
        'project_id',
        'disk',
        'path',
        'relative_path',
        'file_name',
        'base_name',
        'extension',
        'mime_type',
        'language',
        'category',
        'size',
        'line_count',
        'hash',
        'is_source',
        'is_config',
        'is_test',
        'is_vendor',
        'is_binary',
        'is_hidden',
        'is_readable',
        'metadata',
        'discovered_at',
    ];

    protected $casts = [
        'size' => 'integer',
        'line_count' => 'integer',
        'is_source' => 'boolean',
        'is_config' => 'boolean',
        'is_test' => 'boolean',
        'is_vendor' => 'boolean',
        'is_binary' => 'boolean',
        'is_hidden' => 'boolean',
        'is_readable' => 'boolean',
        'metadata' => 'array',
        'discovered_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}