<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analysis_runs', function (Blueprint $table) {
            $table->string('source_type')->nullable()->after('triggered_by_id');
            $table->string('source_reference')->nullable()->after('source_type');
            $table->string('source_branch')->nullable()->after('source_reference');
            $table->string('source_commit_sha', 100)->nullable()->after('source_branch');
            $table->string('external_event_id')->nullable()->after('source_commit_sha');
        });
    }

    public function down(): void
    {
        Schema::table('analysis_runs', function (Blueprint $table) {
            $table->dropColumn([
                'source_type',
                'source_reference',
                'source_branch',
                'source_commit_sha',
                'external_event_id',
            ]);
        });
    }
};