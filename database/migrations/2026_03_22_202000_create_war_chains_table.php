<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('war_chains', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('war_id');
            $table->unsignedBigInteger('faction_id');
            $table->integer('current_chain')->default(0);
            $table->integer('max_chain')->default(0);
            $table->integer('chain_hits')->default(0);
            $table->decimal('chain_respect', 10, 2)->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['war_id', 'faction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('war_chains');
    }
};