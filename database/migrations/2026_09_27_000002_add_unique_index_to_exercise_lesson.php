<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * An exercise's order is its position in the lesson, so two exercises of
     * one lesson cannot share it, but only a plain index stood on the pair.
     * Lessons that already hold a tie are renumbered 0, 1, 2… in their current
     * order, ties broken by exercise_id, which keeps the sequence students see;
     * lessons without a tie keep their numbers. The unique index replaces the
     * plain one, since it serves the same lookups.
     */
    public function up(): void
    {
        $lessonIds = DB::table('exercise_lesson')
            ->select('lesson_id')
            ->groupBy('lesson_id', 'order')
            ->havingRaw('count(*) > 1')
            ->distinct()
            ->pluck('lesson_id');

        foreach ($lessonIds as $lessonId) {
            $exerciseIds = DB::table('exercise_lesson')
                ->where('lesson_id', $lessonId)
                ->orderBy('order')
                ->orderBy('exercise_id')
                ->pluck('exercise_id');

            foreach ($exerciseIds as $position => $exerciseId) {
                DB::table('exercise_lesson')
                    ->where('lesson_id', $lessonId)
                    ->where('exercise_id', $exerciseId)
                    ->update(['order' => $position]);
            }
        }

        Schema::table('exercise_lesson', function (Blueprint $table) {
            $table->dropIndex(['lesson_id', 'order']);
            $table->unique(['lesson_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exercise_lesson', function (Blueprint $table) {
            $table->dropUnique(['lesson_id', 'order']);
            $table->index(['lesson_id', 'order']);
        });
    }
};
