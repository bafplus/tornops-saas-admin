<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('war_attacks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('war_id');
            $table->unsignedBigInteger('attacker_id');
            $table->string('attacker_name')->nullable();
            $table->unsignedBigInteger('defender_id');
            $table->string('defender_name')->nullable();
            $table->string('result')->nullable();
            $table->string('stealthed')->nullable();
            $table->string('fair_fight')->nullable();
            $table->string('respect_gain')->nullable();
            $table->timestamp('timestamp')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index('war_id');
            $table->index('attacker_id');
            $table->index('defender_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('war_attacks');
    }
};
