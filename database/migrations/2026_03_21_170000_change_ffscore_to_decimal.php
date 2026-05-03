<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('war_members', function (Blueprint $table) {
            $table->decimal('ff_score', 5, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('war_members', function (Blueprint $table) {
            $table->integer('ff_score')->nullable()->change();
        });
    }
};
