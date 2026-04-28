<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_issue_insights', function (Blueprint $table) {
            $table->id();

            $table->foreignId('issue_id')
                ->constrained('issues')
                ->cascadeOnDelete();

            $table->foreignId('analysis_run_id')
                ->constrained('analysis_runs')
                ->cascadeOnDelete();

            $table->string('title')->nullable();
            $table->text('explanation')->nullable();
            $table->text('impact')->nullable();
            $table->text('root_cause')->nullable();
            $table->text('fix_suggestion')->nullable();
            $table->text('priority_note')->nullable();

            $table->decimal('confidence_score', 5, 2)->nullable();

            $table->json('evidence')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('issue_id');
            $table->index('analysis_run_id');
            $table->unique(['issue_id'], 'ai_issue_insights_issue_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_issue_insights');
    }
};