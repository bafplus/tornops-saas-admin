<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faction_members', function (Blueprint $table) {
            $table->timestamp('travel_started_at')->nullable()->after('status_changed_at');
        });
    }

    public function down(): void
    {
        Schema::table('faction_members', function (Blueprint $table) {
            $table->dropColumn('travel_started_at');
        });
    }
};
