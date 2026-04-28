<?php

namespace App\Modules\Languages\Models;

use Illuminate\Database\Eloquent\Model;

class SupportedLanguage extends Model
{
    protected $table = 'supported_languages';

    protected $fillable = [
        'code',
        'name',
        'extensions',
        'is_active',
        'sort_order',
        'description',
    ];

    protected $casts = [
        'extensions' => 'array',
        'is_active' => 'boolean',
    ];
}