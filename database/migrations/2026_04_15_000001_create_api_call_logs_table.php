<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint');
            $table->string('job_command')->nullable();
            $table->integer('calls_count')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->index('created_at');
            $table->index('job_command');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_call_logs');
    }
};