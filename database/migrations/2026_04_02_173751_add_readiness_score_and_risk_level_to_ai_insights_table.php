<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_insights', function (Blueprint $table) {
            $table->unsignedTinyInteger('readiness_score')->nullable()->after('overall_health');
            $table->string('risk_level')->nullable()->after('readiness_score');

            $table->index('readiness_score');
            $table->index('risk_level');
        });
    }

    public function down(): void
    {
        Schema::table('ai_insights', function (Blueprint $table) {
            $table->dropIndex(['readiness_score']);
            $table->dropIndex(['risk_level']);

            $table->dropColumn([
                'readiness_score',
                'risk_level',
            ]);
        });
    }
};