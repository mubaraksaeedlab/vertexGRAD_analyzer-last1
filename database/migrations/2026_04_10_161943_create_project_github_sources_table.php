<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_github_sources', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('github_repository_id')->constrained('github_repositories')->cascadeOnDelete();

            $table->string('branch')->default('main');
            $table->string('path_prefix')->nullable();

            $table->string('last_commit_sha', 100)->nullable();

            $table->boolean('auto_sync')->default(false);
            $table->boolean('analyze_on_push')->default(false);
            $table->boolean('analyze_on_pull_request')->default(false);

            $table->unsignedBigInteger('created_by_user_id')->nullable();

            $table->timestamps();

            $table->unique(['project_id', 'github_repository_id'], 'project_repo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_github_sources');
    }
};