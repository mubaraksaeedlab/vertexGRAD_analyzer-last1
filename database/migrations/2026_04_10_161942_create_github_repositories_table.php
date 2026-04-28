<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('github_repositories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('github_repository_id')->unique();
            $table->unsignedBigInteger('github_installation_id');

            $table->string('full_name');
            $table->string('owner');
            $table->string('name');
            $table->boolean('is_private')->default(false);
            $table->string('default_branch')->nullable();
            $table->string('language')->nullable();

            $table->string('html_url')->nullable();
            $table->string('clone_url')->nullable();

            $table->timestamp('last_pushed_at')->nullable();
            $table->boolean('is_active')->default(true);

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['github_installation_id']);
            $table->index(['full_name']);
            $table->index(['owner', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('github_repositories');
    }
};