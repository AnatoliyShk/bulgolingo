<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The composite primary key leads with learning_path_id, so the per-user
     * lookups every enrolled-path read makes — Lesson::getCompletedLessonStats()
     * and User::enrolledPathsWithProgress() both filter on user_id alone — have
     * no usable index and fall back to a sequential scan. Postgres does not
     * index a foreign key's referencing column on its own, so nothing else
     * covers it either, which also makes deleting a user scan the whole pivot.
     *
     * Ordered (user_id, learning_path_id) rather than user_id alone: the pair is
     * exactly what the stats fan-out reads, so that query can be served from the
     * index without touching the table, and user_id leading still serves the
     * plain lookups and the cascade.
     */
    public function up(): void
    {
        Schema::table('learning_path_user', function (Blueprint $table) {
            $table->index(['user_id', 'learning_path_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_path_user', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'learning_path_id']);
        });
    }
};
