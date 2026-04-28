<?php

namespace App\Modules\Understanding\Models;

use App\Modules\Analysis\Models\AnalysisRun;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodeRelationship extends Model
{
    protected $table = "code_relationships";

    protected $fillable = [
        "analysis_run_id",
        "source_entity_id",
        "target_entity_id",
        "relationship_type",
        "metadata",
    ];

    protected $casts = [
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