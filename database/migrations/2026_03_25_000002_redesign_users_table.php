<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_unique');
            $table->dropColumn('email');
            $table->dropColumn('email_verified_at');
            
            $table->enum('status', ['active', 'invited', 'disabled'])->default('invited')->after('is_admin');
            $table->string('invitation_token', 64)->nullable()->unique()->after('status');
            $table->unsignedBigInteger('invited_by')->nullable()->after('invitation_token');
            $table->timestamp('invited_at')->nullable()->after('invited_by');
            $table->timestamp('disabled_at')->nullable()->after('invited_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            
            $table->dropColumn('status');
            $table->dropColumn('invitation_token');
            $table->dropColumn('invited_by');
            $table->dropColumn('invited_at');
            $table->dropColumn('disabled_at');
        });
    }
};