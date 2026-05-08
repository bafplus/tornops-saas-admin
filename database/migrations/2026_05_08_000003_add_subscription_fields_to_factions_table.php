<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factions', function (Blueprint $table) {
            $table->string('subscription_type')->default('free')->after('monthly_cost');
            $table->timestamp('subscription_start')->nullable()->after('subscription_type');
            $table->timestamp('expires_at')->nullable()->after('subscription_start');
            $table->string('payment_item')->default('xanax')->after('amount');
            $table->integer('payment_amount')->default(1)->after('payment_item');
            $table->boolean('trial_used')->default(false)->after('payment_amount');
        });
    }

    public function down(): void
    {
        Schema::table('factions', function (Blueprint $table) {
            $table->dropColumn(['subscription_type', 'subscription_start', 'expires_at', 'payment_item', 'payment_amount', 'trial_used']);
        });
    }
};
