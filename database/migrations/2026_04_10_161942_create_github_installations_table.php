<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('github_installations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('github_installation_id')->unique();
            $table->unsignedBigInteger('github_app_id')->nullable();

            $table->string('account_type')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('account_login')->nullable();
            $table->string('account_avatar_url')->nullable();

            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();

            $table->unsignedBigInteger('installed_by_user_id')->nullable();

            $table->json('permissions')->nullable();
            $table->json('events')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            $table->index(['account_login']);
            $table->index(['installed_by_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('github_installations');
    }
};