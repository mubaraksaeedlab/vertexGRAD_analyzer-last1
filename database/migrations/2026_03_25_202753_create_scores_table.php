<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            $table->foreignId('analysis_run_id')
                ->constrained('analysis_runs')
                ->cascadeOnDelete();

            $table->decimal('overall_score', 5, 2)->default(0);
            $table->decimal('security_score', 5, 2)->default(0);
            $table->decimal('quality_score', 5, 2)->default(0);
            $table->decimal('performance_score', 5, 2)->default(0);
            $table->decimal('structure_score', 5, 2)->default(0);
            $table->decimal('maintainability_score', 5, 2)->default(0);

            $table->unsignedInteger('issues_count')->default(0);
            $table->unsignedInteger('critical_count')->default(0);
            $table->unsignedInteger('high_count')->default(0);
            $table->unsignedInteger('medium_count')->default(0);
            $table->unsignedInteger('low_count')->default(0);
            $table->unsignedInteger('info_count')->default(0);

            $table->string('grade', 10)->nullable()->index();

            $table->json('breakdown')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique('analysis_run_id');
            $table->index(['project_id', 'overall_score']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE scores ADD CONSTRAINT chk_scores_overall_score CHECK (overall_score >= 0 AND overall_score <= 100)');
            DB::statement('ALTER TABLE scores ADD CONSTRAINT chk_scores_security_score CHECK (security_score >= 0 AND security_score <= 100)');
            DB::statement('ALTER TABLE scores ADD CONSTRAINT chk_scores_quality_score CHECK (quality_score >= 0 AND quality_score <= 100)');
            DB::statement('ALTER TABLE scores ADD CONSTRAINT chk_scores_performance_score CHECK (performance_score >= 0 AND performance_score <= 100)');
            DB::statement('ALTER TABLE scores ADD CONSTRAINT chk_scores_structure_score CHECK (structure_score >= 0 AND structure_score <= 100)');
            DB::statement('ALTER TABLE scores ADD CONSTRAINT chk_scores_maintainability_score CHECK (maintainability_score >= 0 AND maintainability_score <= 100)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
};