<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overdose_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('faction_id');
            $table->unsignedBigInteger('player_id');
            $table->integer('count_at_time')->nullable();
            $table->string('drug_id')->nullable();
            $table->timestamp('detected_at');
            $table->timestamps();
            $table->index(['faction_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overdose_events');
    }
};
