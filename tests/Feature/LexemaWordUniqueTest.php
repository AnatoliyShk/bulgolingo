<?php

namespace Tests\Feature;

use App\Models\Lexema;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class LexemaWordUniqueTest extends TestCase
{
    use DatabaseTransactions;

    public function test_a_second_row_for_the_same_word_is_rejected(): void
    {
        Lexema::create(['word' => 'вода']);

        $this->expectException(QueryException::class);

        DB::table('lexemas')->insert(['uuid' => (string) Str::uuid7(), 'word' => 'вода']);
    }

    public function test_first_or_create_returns_the_existing_word(): void
    {
        $lexema = Lexema::create(['word' => 'вода']);

        $this->assertSame($lexema->id, Lexema::firstOrCreate(['word' => 'вода'])->id);
        $this->assertSame(1, Lexema::where('word', 'вода')->count());
    }

    /**
     * Drops the index to write three rows for one word, the way a race left
     * them, with one user holding progress on two of them and another user on
     * one, plus a review log on a duplicate. Running the migration forward has
     * to leave the oldest row alone, fold the first user's progress into the
     * most recently reviewed row with summed counters, repoint the second
     * user's row and the log, and delete the duplicates. Postgres runs the DDL
     * inside the test's transaction, so the schema is restored when it ends.
     */
    public function test_the_migration_merges_duplicate_words_onto_the_oldest_row(): void
    {
        $migration = require database_path('migrations/2026_09_25_000001_add_unique_index_to_lexemas_word.php');
        $migration->down();

        $lexema = fn () => DB::table('lexemas')->insertGetId(['uuid' => (string) Str::uuid7(), 'word' => 'хляб']);
        $keep = $lexema();
        $older = $lexema();
        $newer = $lexema();
        $other = Lexema::create(['word' => 'сирене'])->id;

        [$first, $second] = User::factory()->count(2)->create()->all();

        $progress = fn (int $userId, int $lexemaId, int $reps, int $lapses, ?string $reviewedAt) => DB::table('user_lexema')->insertGetId([
            'user_id' => $userId,
            'lexema_id' => $lexemaId,
            'reps_total' => $reps,
            'lapses' => $lapses,
            'stability' => $reps,
            'last_reviewed_at' => $reviewedAt,
        ]);

        $progress($first->id, $older, 2, 1, '2026-09-01 10:00:00');
        $latest = $progress($first->id, $newer, 3, 0, '2026-09-20 10:00:00');
        $moved = $progress($second->id, $newer, 4, 2, null);
        $untouched = $progress($first->id, $other, 1, 0, null);

        $log = DB::table('review_logs')->insertGetId([
            'user_id' => $first->id,
            'lexema_id' => $newer,
            'rating' => 3,
            'stability_after' => 1,
            'difficulty_after' => 1,
            'elapsed_seconds' => 0,
            'scheduled_days' => 1,
            'scheduler' => 'fsrs',
            'reviewed_at' => now(),
        ]);

        $migration->up();

        $this->assertSame([$keep], DB::table('lexemas')->where('word', 'хляб')->pluck('id')->all());

        $firstRows = DB::table('user_lexema')->where('user_id', $first->id)->where('lexema_id', $keep)->get();
        $this->assertCount(1, $firstRows);
        $this->assertSame($latest, $firstRows[0]->id);
        $this->assertSame(5, (int) $firstRows[0]->reps_total);
        $this->assertSame(1, (int) $firstRows[0]->lapses);
        $this->assertEquals(3, $firstRows[0]->stability);

        $this->assertSame($keep, (int) DB::table('user_lexema')->find($moved)->lexema_id);
        $this->assertSame($other, (int) DB::table('user_lexema')->find($untouched)->lexema_id);
        $this->assertSame($keep, (int) DB::table('review_logs')->find($log)->lexema_id);
    }
}
