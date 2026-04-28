<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("code_flows", function (Blueprint $table) {
            $table->id();
            $table->foreignId("analysis_run_id")->constrained("analysis_runs")->cascadeOnDelete();

            $table->string("flow_type", 50);
            $table->foreignId("source_entity_id")->nullable()->constrained("code_entities")->nullOnDelete();
            $table->foreignId("target_entity_id")->nullable()->constrained("code_entities")->nullOnDelete();

            $table->string("risk_level", 20)->nullable();
            $table->json("evidence")->nullable();
            $table->json("metadata")->nullable();

            $table->timestamps();

            $table->index(["analysis_run_id", "flow_type"]);
            $table->index(["analysis_run_id", "risk_level"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("code_flows");
    }
};