<?php

namespace App\Modules\Understanding\Models;

use App\Modules\Analysis\Models\AnalysisRun;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DependencySnapshot extends Model
{
    protected $table = "dependency_snapshots";

    protected $fillable = [
        "analysis_run_id",
        "graph",
        "summary",
    ];

    protected $casts = [
        "graph" => "array",
        "summary" => "array",
    ];

    public function analysisRun(): BelongsTo
    {
        return $this->belongsTo(AnalysisRun::class, "analysis_run_id");
    }
}