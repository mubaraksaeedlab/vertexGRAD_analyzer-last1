<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_files', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            $table->string('disk', 50)->default('local')->index();

            $table->string('path');
            $table->string('relative_path')->index();

            $table->string('file_name');
            $table->string('base_name')->nullable();
            $table->string('extension', 30)->nullable()->index();
            $table->string('mime_type')->nullable();

            $table->string('language', 50)->nullable()->index();
            $table->string('category', 50)->default('other')->index();

            $table->unsignedBigInteger('size')->default(0);
            $table->unsignedBigInteger('line_count')->default(0);

            $table->string('hash', 64)->nullable()->index();

            $table->boolean('is_source')->default(false)->index();
            $table->boolean('is_config')->default(false)->index();
            $table->boolean('is_test')->default(false)->index();
            $table->boolean('is_vendor')->default(false)->index();
            $table->boolean('is_binary')->default(false)->index();
            $table->boolean('is_hidden')->default(false)->index();
            $table->boolean('is_readable')->default(true)->index();

            $table->json('metadata')->nullable();

            $table->timestamp('discovered_at')->nullable()->index();

            $table->timestamps();

            $table->unique(['project_id', 'relative_path'], 'project_files_project_path_unique');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE project_files ADD CONSTRAINT chk_project_files_size CHECK (size >= 0)');
            DB::statement('ALTER TABLE project_files ADD CONSTRAINT chk_project_files_line_count CHECK (line_count >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_files');
    }
};