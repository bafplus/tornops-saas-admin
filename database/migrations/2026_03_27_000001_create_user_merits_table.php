<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_merits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('merit_name');
            $table->tinyInteger('current_level')->default(0);
            $table->tinyInteger('planned_level')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'merit_name']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('merit_points_available')->default(0)->after('custom_percentages');
            $table->integer('merit_points_used')->default(0)->after('merit_points_available');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_merits');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['merit_points_available', 'merit_points_used']);
        });
    }
};
