<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_insights', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            $table->foreignId('analysis_run_id')
                ->constrained('analysis_runs')
                ->cascadeOnDelete();

            $table->string('model_name')->nullable();
            $table->string('model_version')->nullable();

            $table->string('status')->default('pending');

            $table->string('maturity_level')->nullable();
            $table->string('overall_health')->nullable();

            $table->text('summary')->nullable();
            $table->longText('architecture_review')->nullable();
            $table->longText('risk_assessment')->nullable();
            $table->longText('decision_support')->nullable();

            $table->json('strengths')->nullable();
            $table->json('weaknesses')->nullable();
            $table->json('top_risks')->nullable();
            $table->json('recommendations')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index('project_id');
            $table->index('analysis_run_id');
            $table->index('status');
            $table->unique(['project_id', 'analysis_run_id'], 'ai_insights_project_run_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_insights');
    }
};