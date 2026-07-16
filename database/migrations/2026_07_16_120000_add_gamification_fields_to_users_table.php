<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('points')->default(0)->after('semester_id');
            $table->unsignedInteger('streak')->default(0)->after('points');
            $table->unsignedInteger('daily_goal')->default(3)->after('streak');
            $table->unsignedInteger('daily_goal_done')->default(0)->after('daily_goal');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['points', 'streak', 'daily_goal', 'daily_goal_done']);
        });
    }
};
