<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if column already exists
        $columns = DB::select("PRAGMA table_info(scheduled_jobs)");
        $hasColumn = collect($columns)->contains('name', 'last_run_at');
        
        if (!$hasColumn) {
            Schema::table('scheduled_jobs', function (Blueprint $table) {
                $table->timestamp('last_run_at')->nullable()->after('war_cron');
            });
        }
    }

    public function down(): void
    {
        Schema::table('scheduled_jobs', function (Blueprint $table) {
            $table->dropColumn('last_run_at');
        });
    }
};
