<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('command')->unique();
            $table->string('description');
            $table->boolean('enabled')->default(true);
            $table->string('cron_expression')->default('*/10 * * * *');
            $table->boolean('war_mode_only')->default(false);
            $table->boolean('war_enabled')->default(true);
            $table->string('war_cron')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_jobs');
    }
};
