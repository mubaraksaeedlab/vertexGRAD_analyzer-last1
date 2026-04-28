<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            $table->foreignId('analysis_run_id')
                ->constrained('analysis_runs')
                ->cascadeOnDelete();

            $table->foreignId('project_file_id')
                ->nullable()
                ->constrained('project_files')
                ->nullOnDelete();

            $table->string('rule_code', 100)->index();
            $table->string('category', 50)->index();
            $table->string('severity', 30)->index();

            $table->string('language', 50)->nullable()->index();

            $table->string('title');
            $table->text('description')->nullable();
            $table->text('recommendation')->nullable();

            $table->unsignedInteger('line_start')->nullable();
            $table->unsignedInteger('line_end')->nullable();
            $table->unsignedInteger('column_start')->nullable();
            $table->unsignedInteger('column_end')->nullable();

            $table->longText('snippet')->nullable();

            $table->decimal('confidence', 5, 2)->nullable()->index();
            $table->boolean('is_resolved')->default(false)->index();
            $table->timestamp('resolved_at')->nullable()->index();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['project_id', 'severity']);
            $table->index(['analysis_run_id', 'category']);
            $table->index(['analysis_run_id', 'rule_code']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE issues ADD CONSTRAINT chk_issues_confidence CHECK (confidence IS NULL OR (confidence >= 0 AND confidence <= 100))');
            DB::statement('ALTER TABLE issues ADD CONSTRAINT chk_issues_line_start CHECK (line_start IS NULL OR line_start >= 0)');
            DB::statement('ALTER TABLE issues ADD CONSTRAINT chk_issues_line_end CHECK (line_end IS NULL OR line_end >= 0)');
            DB::statement('ALTER TABLE issues ADD CONSTRAINT chk_issues_column_start CHECK (column_start IS NULL OR column_start >= 0)');
            DB::statement('ALTER TABLE issues ADD CONSTRAINT chk_issues_column_end CHECK (column_end IS NULL OR column_end >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};