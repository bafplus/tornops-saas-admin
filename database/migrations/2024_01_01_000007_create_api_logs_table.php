<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('service');
            $table->string('endpoint');
            $table->string('method', 10)->default('GET');
            $table->integer('status_code')->nullable();
            $table->text('response')->nullable();
            $table->double('response_time_ms')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['service', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
