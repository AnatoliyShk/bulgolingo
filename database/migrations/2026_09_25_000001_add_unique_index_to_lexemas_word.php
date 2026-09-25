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
     * Every writer looks a lexema up by its word with firstOrCreate, which
     * assumes one row per word, but nothing enforced it, so a race could insert
     * the same word twice. Each set of duplicates collapses onto its oldest row,
     * which keeps the exercise_id of the exercise that first introduced the
     * word. Review logs move over as they are; a user's progress rows for the
     * duplicates fold onto the most recently reviewed one, carrying the summed
     * reps_total and lapses, the same merge the user_lexema unique index used.
     * The surviving progress row is repointed only after its siblings are
     * deleted, so the (user_id, lexema_id) unique index never sees two rows.
     */
    public function up(): void
    {
        $words = DB::table('lexemas')
            ->select('word')
            ->groupBy('word')
            ->havingRaw('count(*) > 1')
            ->pluck('word');

        foreach ($words as $word) {
            $ids = DB::table('lexemas')->where('word', $word)->orderBy('id')->pluck('id');
            $keep = $ids->first();
            $drop = $ids->slice(1)->values();

            DB::table('review_logs')->whereIn('lexema_id', $drop)->update(['lexema_id' => $keep]);

            $progressByUser = DB::table('user_lexema')->whereIn('lexema_id', $ids)->get()->groupBy('user_id');

            foreach ($progressByUser as $rows) {
                $survivor = $rows
                    ->sortByDesc(fn ($row) => [$row->last_reviewed_at ?? '', $row->id])
                    ->first();

                DB::table('user_lexema')
                    ->whereIn('id', $rows->pluck('id'))
                    ->where('id', '!=', $survivor->id)
                    ->delete();

                DB::table('user_lexema')
                    ->where('id', $survivor->id)
                    ->update([
                        'lexema_id' => $keep,
                        'reps_total' => $rows->sum('reps_total'),
                        'lapses' => $rows->sum('lapses'),
                    ]);
            }

            DB::table('lexemas')->whereIn('id', $drop)->delete();
        }

        Schema::table('lexemas', function (Blueprint $table) {
            $table->unique('word');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lexemas', function (Blueprint $table) {
            $table->dropUnique(['word']);
        });
    }
};
