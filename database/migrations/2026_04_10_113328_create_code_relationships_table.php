<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("code_relationships", function (Blueprint $table) {
            $table->id();
            $table->foreignId("analysis_run_id")->constrained("analysis_runs")->cascadeOnDelete();

            $table->foreignId("source_entity_id")->constrained("code_entities")->cascadeOnDelete();
            $table->foreignId("target_entity_id")->nullable()->constrained("code_entities")->nullOnDelete();

            $table->string("relationship_type", 50);
            $table->json("metadata")->nullable();

            $table->timestamps();

            $table->index(["analysis_run_id", "relationship_type"]);
            $table->index(["source_entity_id", "relationship_type"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("code_relationships");
    }
};