<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analysis_runs', function (Blueprint $table) {
            $table->unsignedTinyInteger('progress_percent')->default(0)->after('stage');
            $table->string('current_step', 255)->nullable()->after('progress_percent');
            $table->string('current_file', 1024)->nullable()->after('current_step');
            $table->unsignedInteger('total_files')->default(0)->after('current_file');
            $table->unsignedInteger('processed_files')->default(0)->after('total_files');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE analysis_runs ADD CONSTRAINT chk_analysis_runs_progress_percent CHECK (progress_percent >= 0 AND progress_percent <= 100)');
            DB::statement('ALTER TABLE analysis_runs ADD CONSTRAINT chk_analysis_runs_total_files CHECK (total_files >= 0)');
            DB::statement('ALTER TABLE analysis_runs ADD CONSTRAINT chk_analysis_runs_processed_files CHECK (processed_files >= 0)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Drop CHECK constraints if present (MySQL drops them automatically when column dropped in many versions)
            try {
                DB::statement('ALTER TABLE analysis_runs DROP CHECK chk_analysis_runs_progress_percent');
            } catch (\Throwable $e) {
            }
            try {
                DB::statement('ALTER TABLE analysis_runs DROP CHECK chk_analysis_runs_total_files');
            } catch (\Throwable $e) {
            }
            try {
                DB::statement('ALTER TABLE analysis_runs DROP CHECK chk_analysis_runs_processed_files');
            } catch (\Throwable $e) {
            }
        }

        Schema::table('analysis_runs', function (Blueprint $table) {
            $table->dropColumn(['progress_percent', 'current_step', 'current_file', 'total_files', 'processed_files']);
        });
    }
};
