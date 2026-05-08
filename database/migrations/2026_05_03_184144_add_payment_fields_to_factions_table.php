<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factions', function (Blueprint $table) {
            $table->enum('payment', ['Paid', 'Due', 'Disabled'])->default('Due')->after('is_trial');
            $table->integer('amount')->default(0)->after('payment');
        });
    }

    public function down(): void
    {
        Schema::table('factions', function (Blueprint $table) {
            $table->dropColumn(['payment', 'amount']);
        });
    }
};
