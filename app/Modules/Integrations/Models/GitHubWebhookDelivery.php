<?php

namespace App\Modules\Integrations\Models;

use Illuminate\Database\Eloquent\Model;

class GitHubWebhookDelivery extends Model
{
    protected $table = 'github_webhook_deliveries';

    protected $fillable = [
        'delivery_id',
        'event',
        'action',
        'github_installation_id',
        'github_repository_id',
        'status',
        'error_message',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];
}