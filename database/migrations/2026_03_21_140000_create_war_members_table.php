<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('war_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('war_id');
            $table->unsignedBigInteger('faction_id');
            $table->unsignedBigInteger('player_id');
            $table->string('name');
            $table->integer('level');
            $table->string('rank')->nullable();
            $table->string('position')->nullable();
            $table->integer('days_in_faction')->nullable();
            $table->string('status_color', 20)->nullable();
            $table->string('status_description')->nullable();
            $table->integer('war_score')->default(0);
            $table->integer('ff_score')->nullable();
            $table->json('estimated_stats')->nullable();
            $table->timestamp('ff_updated_at')->nullable();
            $table->string('online_status', 20)->default('offline');
            $table->string('online_description')->nullable();
            $table->integer('personal_war_score')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
            
            $table->unique(['war_id', 'faction_id', 'player_id']);
            $table->index('war_id');
            $table->index('faction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('war_members');
    }
};
