<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faction_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('faction_id');
            $table->unsignedBigInteger('player_id');
            $table->string('name');
            $table->integer('level')->default(1);
            $table->string('rank')->nullable();
            $table->string('days_in_faction')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['faction_id', 'player_id']);
            $table->index('player_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faction_members');
    }
};
