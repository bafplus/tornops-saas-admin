<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faction_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('faction_id')->unique();
            $table->string('faction_name')->nullable();
            $table->text('torn_api_key')->nullable();
            $table->text('ffscouter_api_key')->nullable();
            $table->boolean('auto_sync_enabled')->default(true);
            $table->json('sync_settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faction_settings');
    }
};
