<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_runs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            $table->uuid('run_uuid')->unique();

            $table->string('trigger_type', 50)->default('manual')->index();
            $table->string('triggered_by_type', 50)->nullable()->index();
            $table->unsignedBigInteger('triggered_by_id')->nullable()->index();

            $table->string('status', 50)->default('pending')->index();
            $table->string('stage', 100)->nullable()->index();

            $table->string('analyzer_version', 50)->nullable()->index();
            $table->string('engine_name', 100)->default('VertexGrad Analyzer')->index();

            $table->unsignedInteger('files_processed')->default(0);
            $table->unsignedInteger('issues_found')->default(0);

            $table->unsignedBigInteger('duration_ms')->default(0);

            $table->text('failure_reason')->nullable();
            $table->json('summary')->nullable();
            $table->json('metrics')->nullable();
            $table->json('context')->nullable();

            $table->timestamp('queued_at')->nullable()->index();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('finished_at')->nullable()->index();

            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'created_at']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE analysis_runs ADD CONSTRAINT chk_analysis_runs_files_processed CHECK (files_processed >= 0)');
            DB::statement('ALTER TABLE analysis_runs ADD CONSTRAINT chk_analysis_runs_issues_found CHECK (issues_found >= 0)');
            DB::statement('ALTER TABLE analysis_runs ADD CONSTRAINT chk_analysis_runs_duration_ms CHECK (duration_ms >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_runs');
    }
};