<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faction_settings', function (Blueprint $table) {
            $table->boolean('discord_enabled')->default(false)->after('auto_sync_enabled');
            $table->string('discord_bot_token')->nullable()->after('discord_enabled');
            $table->unsignedBigInteger('discord_server_id')->nullable()->after('discord_bot_token');
            $table->unsignedBigInteger('discord_channel_id')->nullable()->after('discord_server_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('discord_user_id')->nullable()->after('torn_api_key');
            $table->timestamp('discord_verified_at')->nullable()->after('discord_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['discord_user_id', 'discord_verified_at']);
        });

        Schema::table('faction_settings', function (Blueprint $table) {
            $table->dropColumn(['discord_enabled', 'discord_bot_token', 'discord_server_id', 'discord_channel_id']);
        });
    }
};
