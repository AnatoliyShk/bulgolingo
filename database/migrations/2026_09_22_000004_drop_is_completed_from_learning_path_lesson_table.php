<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lesson completion is a fact about a user and a lesson, but this column hung
 * off (learning_path_id, lesson_id) with no user in the key, so one student
 * finishing a lesson marked it done for everyone else on the path. The flag is
 * also redundant: a lesson is finished exactly when the user has completed all
 * of its exercises, which user_exercise_completions already records, so the
 * path map now derives it per viewer through Lesson::completionMapFor().
 *
 * The down() restores the column but not its values; every row it held was
 * either shared between users or recomputable from the completions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_path_lesson', function (Blueprint $table) {
            $table->dropColumn('is_completed');
        });
    }

    public function down(): void
    {
        Schema::table('learning_path_lesson', function (Blueprint $table) {
            $table->boolean('is_completed')->default(false);
        });
    }
};
