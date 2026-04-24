<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('str_percent')->default(25);
            $table->integer('def_percent')->default(25);
            $table->integer('spd_percent')->default(25);
            $table->integer('dex_percent')->default(25);
            $table->boolean('is_custom')->default(false);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('training_program_id')->nullable()->constrained('training_programs')->nullOnDelete();
            $table->json('custom_percentages')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['training_program_id']);
            $table->dropColumn(['training_program_id', 'custom_percentages']);
        });
        Schema::dropIfExists('training_programs');
    }
};