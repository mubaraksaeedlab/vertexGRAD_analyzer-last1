<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("code_entities", function (Blueprint $table) {
            $table->id();
            $table->foreignId("analysis_run_id")->constrained("analysis_runs")->cascadeOnDelete();
            $table->foreignId("file_id")->nullable()->constrained("analysis_run_files")->nullOnDelete();

            $table->string("entity_type", 50);
            $table->string("name");
            $table->string("qualified_name")->nullable();
            $table->unsignedInteger("start_line")->nullable();
            $table->unsignedInteger("end_line")->nullable();
            $table->json("metadata")->nullable();

            $table->timestamps();

            $table->index(["analysis_run_id", "entity_type"]);
            $table->index(["analysis_run_id", "name"]);
            $table->index("qualified_name");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("code_entities");
    }
};