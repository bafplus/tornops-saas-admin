<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Default settings
        DB::table('admin_settings')->insert([
            ['key' => 'torn_api_key', 'value' => '', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'default_payment_item', 'value' => 'xanax', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'default_payment_amount', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'last_event_run', 'value' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_settings');
    }
};
