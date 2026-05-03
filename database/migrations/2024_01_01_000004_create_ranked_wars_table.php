<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranked_wars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('war_id');
            $table->unsignedBigInteger('faction_id');
            $table->unsignedBigInteger('opponent_faction_id');
            $table->string('opponent_faction_name')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->string('score_ours')->nullable();
            $table->string('score_them')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->unique(['war_id', 'faction_id']);
            $table->index('faction_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranked_wars');
    }
};
