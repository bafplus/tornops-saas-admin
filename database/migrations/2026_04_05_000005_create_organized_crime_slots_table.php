<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organized_crime_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organized_crime_id');
            $table->unsignedBigInteger('oc_id');
            $table->string('position');
            $table->tinyInteger('position_number');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('checkpoint_pass_rate', 5, 2)->nullable();
            $table->unsignedBigInteger('user_joined_at')->nullable();
            $table->unsignedBigInteger('item_required_id')->nullable();
            $table->boolean('item_available')->default(false);
            $table->timestamp('last_synced_at')->useCurrent();
            $table->timestamps();
            $table->index(['oc_id', 'position']);
            $table->index(['user_id']);
            $table->foreign('organized_crime_id')->references('id')->on('organized_crimes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organized_crime_slots');
    }
};
