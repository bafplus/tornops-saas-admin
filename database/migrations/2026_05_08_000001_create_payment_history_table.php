<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('faction_id')->nullable();
            $table->unsignedBigInteger('event_id')->unique()->nullable();
            $table->string('item_name')->default('xanax');
            $table->integer('quantity')->default(0);
            $table->text('description')->nullable();
            $table->string('payer_name')->nullable();
            $table->unsignedBigInteger('payer_id')->nullable();
            $table->integer('extension_days')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('matched_instance')->default(false);
            $table->boolean('manual')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_history');
    }
};
