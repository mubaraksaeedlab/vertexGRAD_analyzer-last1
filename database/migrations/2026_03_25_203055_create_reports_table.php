<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            $table->foreignId('analysis_run_id')
                ->constrained('analysis_runs')
                ->cascadeOnDelete();

            $table->string('report_type', 50)->index(); // json, pdf, dashboard

            $table->string('title')->nullable();

            $table->string('file_path')->nullable();
            $table->string('file_disk', 50)->default('local');

            $table->unsignedBigInteger('file_size')->default(0);

            $table->json('report_data')->nullable();

            $table->string('version', 50)->nullable();
            $table->string('generator', 100)->nullable();

            $table->timestamp('generated_at')->nullable()->index();

            $table->timestamps();

            $table->index(['project_id', 'report_type']);
            $table->index(['analysis_run_id', 'report_type']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE reports ADD CONSTRAINT chk_reports_file_size CHECK (file_size >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};