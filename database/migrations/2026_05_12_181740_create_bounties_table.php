<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bounties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('target_id');
            $table->string('target_name');
            $table->integer('target_level');
            $table->unsignedBigInteger('lister_id')->nullable();
            $table->string('lister_name')->nullable();
            $table->bigInteger('reward');
            $table->string('reason')->nullable();
            $table->integer('quantity')->default(1);
            $table->boolean('is_anonymous')->default(false);
            $table->unsignedBigInteger('valid_until');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('target_id');
            $table->index('reward');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bounties');
    }
};
