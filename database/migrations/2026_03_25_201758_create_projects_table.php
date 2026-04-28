<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();
            $table->string('token', 100)->unique();

            $table->string('name');
            $table->string('slug')->nullable()->index();

            $table->string('source_type', 50)->default('upload')->index();
            $table->string('source_name')->nullable();
            $table->string('source_path')->nullable();
            $table->string('archive_path')->nullable();
            $table->string('extracted_path')->nullable();

            $table->string('primary_language', 50)->nullable()->index();
            $table->json('detected_languages')->nullable();

            $table->unsignedBigInteger('total_files')->default(0);
            $table->unsignedBigInteger('source_files')->default(0);
            $table->unsignedBigInteger('total_lines')->default(0);

            $table->unsignedBigInteger('archive_size')->default(0);
            $table->unsignedBigInteger('extracted_size')->default(0);

            $table->string('status', 50)->default('draft')->index();
            $table->string('scan_status', 50)->default('pending')->index();

            $table->string('owner_name')->nullable();
            $table->string('owner_email')->nullable()->index();

            $table->unsignedBigInteger('platform_project_id')->nullable()->index();
            $table->string('external_reference')->nullable()->index();
            $table->string('external_source')->nullable()->index();
            $table->string('integration_mode', 50)->nullable()->index();

            $table->text('callback_url')->nullable();

            $table->json('metadata')->nullable();
            $table->json('summary')->nullable();

            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE projects ADD CONSTRAINT chk_projects_total_files CHECK (total_files >= 0)');
            DB::statement('ALTER TABLE projects ADD CONSTRAINT chk_projects_source_files CHECK (source_files >= 0)');
            DB::statement('ALTER TABLE projects ADD CONSTRAINT chk_projects_total_lines CHECK (total_lines >= 0)');
            DB::statement('ALTER TABLE projects ADD CONSTRAINT chk_projects_archive_size CHECK (archive_size >= 0)');
            DB::statement('ALTER TABLE projects ADD CONSTRAINT chk_projects_extracted_size CHECK (extracted_size >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};