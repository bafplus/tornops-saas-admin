<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faction_settings', function (Blueprint $table) {
            $table->tinyInteger('travel_method')->default(1)->after('auto_sync_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('faction_settings', function (Blueprint $table) {
            $table->dropColumn('travel_method');
        });
    }
};
