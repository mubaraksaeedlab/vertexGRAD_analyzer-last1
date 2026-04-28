<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('github_webhook_deliveries', function (Blueprint $table) {
            $table->id();

            $table->string('delivery_id')->unique();
            $table->string('event');
            $table->string('action')->nullable();

            $table->unsignedBigInteger('github_installation_id')->nullable();
            $table->unsignedBigInteger('github_repository_id')->nullable();

            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();

            $table->json('payload')->nullable();

            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->index(['event']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('github_webhook_deliveries');
    }
};