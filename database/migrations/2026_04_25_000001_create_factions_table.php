<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factions', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name');
            $table->integer('torn_faction_id');
            $table->string('status', 20)->default('pending');
            $table->boolean('is_trial')->default(false);
            $table->integer('monthly_cost')->default(0);
            $table->integer('payment_item_id')->nullable();
            $table->timestamp('payment_item_last_checked')->nullable();
            $table->string('container_name')->nullable();
            $table->string('db_path')->nullable();
            $table->string('master_key', 64)->nullable();
            $table->timestamp('master_key_generated_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factions');
    }
};