<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faction_settings', function (Blueprint $table) {
            $table->boolean('war_mode_enabled')->default(false)->after('discord_channel_id');
        });
    }

    public function down(): void
    {
        Schema::table('faction_settings', function (Blueprint $table) {
            $table->dropColumn('war_mode_enabled');
        });
    }
};