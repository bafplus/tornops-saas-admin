<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faction_members', function (Blueprint $table) {
            $table->string('position')->nullable()->after('rank');
            $table->string('status_description')->nullable()->after('days_in_faction');
            $table->string('status_color', 20)->nullable()->after('status_description');
            $table->string('online_status', 20)->nullable()->after('status_color');
            $table->string('ff_score')->nullable()->after('level');
            $table->string('estimated_stats')->nullable()->after('ff_score');
            $table->timestamp('status_changed_at')->nullable()->after('online_status');
        });
    }

    public function down(): void
    {
        Schema::table('faction_members', function (Blueprint $table) {
            $table->dropColumn(['position', 'status_description', 'status_color', 'online_status', 'ff_score', 'estimated_stats', 'status_changed_at']);
        });
    }
};
