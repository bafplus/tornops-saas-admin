<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_history', function (Blueprint $table) {
            $table->id();
            $table->integer('stock_id');
            $table->string('name');
            $table->string('acronym');
            $table->decimal('price', 12, 2);
            $table->bigInteger('investors')->default(0);
            $table->bigInteger('shares')->default(0);
            $table->decimal('market_cap', 20, 2)->nullable();
            $table->date('recorded_at');
            $table->timestamps();
            
            $table->index(['stock_id', 'recorded_at']);
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_history');
    }
};
