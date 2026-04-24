<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organized_crimes', function (Blueprint $table) {
            $table->unsignedBigInteger('previous_crime_id')->nullable()->after('oc_id');
            $table->json('rewards')->nullable()->after('expires_at');
        });

        Schema::table('organized_crime_slots', function (Blueprint $table) {
            $table->string('position_id')->nullable()->after('position');
            $table->string('user_outcome')->nullable()->after('user_id');
            $table->decimal('user_progress', 5, 2)->nullable()->after('user_outcome');
            $table->json('item_outcome')->nullable()->after('item_available');
        });
    }

    public function down(): void
    {
        Schema::table('organized_crime_slots', function (Blueprint $table) {
            $table->dropColumn(['position_id', 'user_outcome', 'user_progress', 'item_outcome']);
        });

        Schema::table('organized_crimes', function (Blueprint $table) {
            $table->dropColumn(['previous_crime_id', 'rewards']);
        });
    }
};
