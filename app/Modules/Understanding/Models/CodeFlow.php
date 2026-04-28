<?php

namespace App\Modules\Understanding\Models;

use App\Modules\Analysis\Models\AnalysisRun;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodeFlow extends Model
{
    protected $table = "code_flows";

    protected $fillable = [
        "analysis_run_id",
        "flow_type",
        "source_entity_id",
        "target_entity_id",
        "risk_level",
        "evidence",
        "metadata",
    ];

    protected $casts = [
        "evidence" => "array",
        "metadata" => "array",
    ];

    public function analysisRun(): BelongsTo
    {
        return $this->belongsTo(AnalysisRun::class, "analysis_run_id");
    }

    public function sourceEntity(): BelongsTo
    {
        return $this->belongsTo(CodeEntity::class, "source_entity_id");
    }

    public function targetEntity(): BelongsTo
    {
        return $this->belongsTo(CodeEntity::class, "target_entity_id");
    }
}