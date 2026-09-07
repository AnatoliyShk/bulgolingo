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
     * The pivot has always been meant to hold one row per user/lexema — the FSRS
     * columns and LexemaCountUpdate's "increment, else attach" both assume it — but
     * nothing enforced it, so a race could duplicate a pair. Collapse any existing
     * duplicates onto the most recently reviewed row (it carries the current FSRS
     * state), carrying over the summed reps_total and lapses, before the index is
     * added; otherwise the unique constraint cannot be created.
     */
    public function up(): void
    {
        $pairs = DB::table('user_lexema')
            ->select('user_id', 'lexema_id')
            ->groupBy('user_id', 'lexema_id')
            ->havingRaw('count(*) > 1')
            ->get();

        foreach ($pairs as $pair) {
            $rows = DB::table('user_lexema')
                ->where('user_id', $pair->user_id)
                ->where('lexema_id', $pair->lexema_id)
                ->get();

            $survivor = $rows
                ->sortByDesc(fn ($row) => [$row->last_reviewed_at ?? '', $row->id])
                ->first();

            DB::table('user_lexema')
                ->where('id', $survivor->id)
                ->update([
                    'reps_total' => $rows->sum('reps_total'),
                    'lapses' => $rows->sum('lapses'),
                ]);

            DB::table('user_lexema')
                ->where('user_id', $pair->user_id)
                ->where('lexema_id', $pair->lexema_id)
                ->where('id', '!=', $survivor->id)
                ->delete();
        }

        Schema::table('user_lexema', function (Blueprint $table) {
            $table->unique(['user_id', 'lexema_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_lexema', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'lexema_id']);
        });
    }
};
