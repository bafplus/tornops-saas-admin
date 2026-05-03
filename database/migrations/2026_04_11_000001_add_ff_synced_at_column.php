<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faction_members', function (Blueprint $table) {
            $table->timestamp('ff_synced_at')->nullable()->after('estimated_stats');
        });
    }

    public function down(): void
    {
        Schema::table('faction_members', function (Blueprint $table) {
            $table->dropColumn('ff_synced_at');
        });
    }
};