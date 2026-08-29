<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exercises move from a single `lesson_id` to a many-to-many pivot so the
     * same exercise can be reused across lessons. `order` is the sequence a
     * student works through a lesson in, which used to be implied by the
     * exercise id — the backfill below preserves exactly that order.
     */
    public function up(): void
    {
        Schema::create('exercise_lesson', function (Blueprint $table) {
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0);
            $table->primary(['lesson_id', 'exercise_id']);
            $table->index(['lesson_id', 'order']);
        });

        $nextOrder = [];
        $rows = [];

        DB::table('exercises')
            ->select('id', 'lesson_id')
            ->orderBy('lesson_id')
            ->orderBy('id')
            ->each(function ($exercise) use (&$nextOrder, &$rows) {
                $order = $nextOrder[$exercise->lesson_id] ?? 0;
                $nextOrder[$exercise->lesson_id] = $order + 1;

                $rows[] = [
                    'lesson_id' => $exercise->lesson_id,
                    'exercise_id' => $exercise->id,
                    'order' => $order,
                ];
            });

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('exercise_lesson')->insert($chunk);
        }

        Schema::table('exercises', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['lesson_id']);
            }

            $table->dropColumn('lesson_id');
        });
    }

    /**
     * Collapses the pivot back to one lesson per exercise, keeping the
     * lowest-ordered attachment when an exercise sits in several lessons.
     */
    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->foreignId('lesson_id')->nullable()->constrained()->cascadeOnDelete();
        });

        DB::table('exercise_lesson')
            ->orderBy('exercise_id')
            ->orderBy('order')
            ->get()
            ->groupBy('exercise_id')
            ->each(function ($attachments, $exerciseId) {
                DB::table('exercises')
                    ->where('id', $exerciseId)
                    ->update(['lesson_id' => $attachments->first()->lesson_id]);
            });

        Schema::dropIfExists('exercise_lesson');
    }
};
