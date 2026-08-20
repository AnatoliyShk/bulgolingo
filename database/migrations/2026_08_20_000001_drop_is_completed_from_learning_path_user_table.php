<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nothing ever wrote this column, so the stats page reported zero completed
     * paths for everyone. Path completion is now derived per-user from
     * `user_exercise_completions`, the same source the lesson and exercise
     * aggregates already use.
     */
    public function up(): void
    {
        Schema::table('learning_path_user', function (Blueprint $table) {
            $table->dropColumn('is_completed');
        });
    }

    public function down(): void
    {
        Schema::table('learning_path_user', function (Blueprint $table) {
            $table->boolean('is_completed')->default(false);
        });
    }
};
