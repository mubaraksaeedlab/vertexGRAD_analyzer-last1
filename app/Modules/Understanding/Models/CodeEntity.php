<?php

namespace App\Modules\Understanding\Models;

use App\Modules\Analysis\Models\AnalysisRun;
use App\Modules\Analysis\Models\AnalysisRunFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CodeEntity extends Model
{
    protected $table = "code_entities";

    protected $fillable = [
        "analysis_run_id",
        "file_id",
        "entity_type",
        "name",
        "qualified_name",
        "start_line",
        "end_line",
        "metadata",
    ];

    protected $casts = [
        "metadata" => "array",
    ];

    public function analysisRun(): BelongsTo
    {
        return $this->belongsTo(AnalysisRun::class, "analysis_run_id");
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(AnalysisRunFile::class, "file_id");
    }

    public function outgoingRelationships(): HasMany
    {
        return $this->hasMany(CodeRelationship::class, "source_entity_id");
    }

    public function incomingRelationships(): HasMany
    {
        return $this->hasMany(CodeRelationship::class, "target_entity_id");
    }
}