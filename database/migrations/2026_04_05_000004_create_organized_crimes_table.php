<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organized_crimes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('faction_id');
            $table->unsignedBigInteger('oc_id')->unique();
            $table->string('name');
            $table->tinyInteger('difficulty');
            $table->string('status'); // planning, recruiting, ready, executed
            $table->unsignedBigInteger('oc_created_at')->nullable();
            $table->unsignedBigInteger('planning_started_at')->nullable();
            $table->unsignedBigInteger('ready_at')->nullable();
            $table->unsignedBigInteger('executed_at')->nullable();
            $table->unsignedBigInteger('expires_at')->nullable();
            $table->timestamp('last_synced_at')->useCurrent();
            $table->timestamps();
            $table->index(['faction_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organized_crimes');
    }
};
