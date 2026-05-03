<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overdose_counts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('faction_id');
            $table->unsignedBigInteger('player_id');
            $table->integer('count')->default(0);
            $table->timestamp('updated_at')->useCurrent();
            $table->unique(['faction_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overdose_counts');
    }
};
