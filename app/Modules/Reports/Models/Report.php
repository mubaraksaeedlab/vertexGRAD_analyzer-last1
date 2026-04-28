<?php

namespace App\Modules\Reports\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Projects\Models\Project;
use App\Modules\Analysis\Models\AnalysisRun;

class Report extends Model
{
    protected $table = 'reports';

    protected $fillable = [
        'project_id',
        'analysis_run_id',
        'report_type',
        'title',
        'file_path',
        'file_disk',
        'file_size',
        'report_data',
        'version',
        'generator',
        'generated_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'report_data' => 'array',
        'generated_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function analysisRun(): BelongsTo
    {
        return $this->belongsTo(AnalysisRun::class);
    }
}