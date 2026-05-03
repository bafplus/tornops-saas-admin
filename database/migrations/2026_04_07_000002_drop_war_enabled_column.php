<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_jobs', function (Blueprint $table) {
            $table->dropColumn('war_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_jobs', function (Blueprint $table) {
            $table->boolean('war_enabled')->default(true);
        });
    }
};
