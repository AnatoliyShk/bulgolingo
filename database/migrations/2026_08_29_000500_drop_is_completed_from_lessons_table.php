<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nothing ever wrote this column — per-user lesson completion is derived
     * from `user_exercise_completions` and tracked per learning path on the
     * `learning_path_lesson` pivot instead.
     */
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('is_completed');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->boolean('is_completed')->default(false)->after('description');
        });
    }
};
