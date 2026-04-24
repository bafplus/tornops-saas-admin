<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('player_profiles', function (Blueprint $table) {
            // Basic profile info
            $table->string('title')->nullable()->after('rank');
            $table->integer('age')->nullable()->after('title');
            $table->integer('signed_up')->nullable()->after('age');
            $table->integer('honor_id')->nullable()->after('signed_up');
            $table->string('property_name')->nullable()->after('honor_id');
            $table->unsignedBigInteger('property_id')->nullable()->after('property_name');
            $table->string('donator_status')->nullable()->after('property_id');
            $table->string('image')->nullable()->after('donator_status');
            $table->string('gender')->nullable()->after('image');
            $table->string('role')->nullable()->after('gender');
            $table->boolean('revivable')->nullable()->after('role');

            // Status info
            $table->string('status_description')->nullable()->after('revivable');
            $table->string('status_details')->nullable()->after('status_description');
            $table->string('status_state')->nullable()->after('status_details');
            $table->string('status_color')->nullable()->after('status_state');
            $table->integer('status_until')->nullable()->after('status_color');

            // Spouse info
            $table->unsignedBigInteger('spouse_id')->nullable()->after('status_until');
            $table->string('spouse_name')->nullable()->after('spouse_id');
            $table->string('spouse_status')->nullable()->after('spouse_name');

            // Stats
            $table->integer('awards')->nullable()->after('spouse_status');
            $table->integer('friends')->nullable()->after('awards');
            $table->integer('enemies')->nullable()->after('friends');
            $table->integer('forum_posts')->nullable()->after('enemies');
            $table->integer('karma')->nullable()->after('forum_posts');

            // Life stats
            $table->integer('life_current')->nullable()->after('karma');
            $table->integer('life_maximum')->nullable()->after('life_current');

            // Last action
            $table->string('last_action_status')->nullable()->after('life_maximum');
            $table->integer('last_action_timestamp')->nullable()->after('last_action_status');

            // Update tracking
            $table->boolean('from_war')->default(false)->after('last_action_timestamp')->comment('True if synced from war opponent');
        });
    }

    public function down(): void
    {
        Schema::table('player_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'title', 'age', 'signed_up', 'honor_id', 'property_name', 'property_id',
                'donator_status', 'image', 'gender', 'role', 'revivable',
                'status_description', 'status_details', 'status_state', 'status_color', 'status_until',
                'spouse_id', 'spouse_name', 'spouse_status',
                'awards', 'friends', 'enemies', 'forum_posts', 'karma',
                'life_current', 'life_maximum',
                'last_action_status', 'last_action_timestamp',
                'from_war'
            ]);
        });
    }
};