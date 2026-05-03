<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->nullable();
            $table->string('sub_type')->nullable();
            $table->boolean('is_tradable')->default(true);
            $table->boolean('is_found_in_city')->default(false);
            $table->unsignedBigInteger('buy_price')->nullable();
            $table->unsignedBigInteger('sell_price')->nullable();
            $table->unsignedBigInteger('market_price')->nullable();
            $table->unsignedBigInteger('circulation')->nullable();
            $table->string('image')->nullable();
            $table->timestamp('last_synced_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
