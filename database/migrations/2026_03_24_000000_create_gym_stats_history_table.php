<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gym_stats_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('strength')->nullable();
            $table->integer('defense')->nullable();
            $table->integer('speed')->nullable();
            $table->integer('dexterity')->nullable();
            $table->string('gym_name')->nullable();
            $table->string('gym_id')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gym_stats_history');
    }
};