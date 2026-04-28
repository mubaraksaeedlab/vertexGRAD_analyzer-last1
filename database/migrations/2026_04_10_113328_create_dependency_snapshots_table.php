<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("dependency_snapshots", function (Blueprint $table) {
            $table->id();
            $table->foreignId("analysis_run_id")->constrained("analysis_runs")->cascadeOnDelete();

            $table->json("graph");
            $table->json("summary")->nullable();

            $table->timestamps();

            $table->unique("analysis_run_id");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("dependency_snapshots");
    }
};