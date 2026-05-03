<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_stock_holdings', function (Blueprint $table) {
            $table->id();
            $table->integer('stock_id');
            $table->string('name')->nullable();
            $table->string('acronym')->nullable();
            $table->integer('shares');
            $table->decimal('avg_price', 12, 2)->nullable();
            $table->decimal('current_price', 12, 2)->nullable();
            $table->decimal('value', 15, 2)->nullable();
            $table->decimal('profit_loss', 15, 2)->nullable();
            $table->decimal('profit_loss_pct', 8, 2)->nullable();
            $table->json('bonus')->nullable();
            $table->integer('user_id');
            $table->date('recorded_at');
            $table->timestamps();
            
            $table->index(['user_id', 'stock_id', 'recorded_at']);
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_stock_holdings');
    }
};
