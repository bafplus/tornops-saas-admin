<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id')->unique();
            $table->string('name')->nullable();
            $table->integer('level')->default(1);
            $table->string('rank')->nullable();
            $table->string('faction_name')->nullable();
            $table->unsignedBigInteger('faction_id')->nullable();
            $table->json('battlestats')->nullable();
            $table->json('personalstats')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('faction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_profiles');
    }
};
