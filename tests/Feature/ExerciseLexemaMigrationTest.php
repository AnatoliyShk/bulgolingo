<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExerciseLexemaMigrationTest extends TestCase
{
    use DatabaseTransactions;

    private function exercise(array $options): int
    {
        return DB::table('exercises')->insertGetId([
            'uuid' => (string) Str::uuid7(),
            'name' => 'Legacy',
            'decision_type' => ExerciseType::FILL_IN_THE_BLANK->value,
            'clause' => json_encode([
                'sentence' => 'The ___ is an animal.',
                'options' => $options,
                'correct_option' => 0,
                'explanation' => 'Test.',
            ]),
        ]);
    }

    /**
     * Rolls the migration back to the old column, then writes what production
     * held before it: one exercise that introduced куче through exercise_id, a
     * later one whose options reuse куче and add котка with no link at all,
     * and a lexema no clause mentions. Running forward has to link both
     * exercises to куче, the second to котка, keep the clause-less lexema's
     * old link, and drop the column. Rows are written through the query
     * builder because the observer would reach for the pivot while the
     * migration is rolled back. Postgres runs the DDL inside the test's
     * transaction, so the schema is restored when it ends.
     */
    public function test_the_migration_links_every_exercise_to_the_words_it_uses(): void
    {
        $migration = require database_path('migrations/2026_09_26_000001_create_exercise_lexema_table.php');
        $migration->down();

        $first = $this->exercise(['Куче']);
        $second = $this->exercise(['Куче', 'Котка']);

        $lexema = fn (string $word, ?int $exerciseId) => DB::table('lexemas')->insertGetId([
            'uuid' => (string) Str::uuid7(),
            'word' => $word,
            'exercise_id' => $exerciseId,
        ]);
        $dog = $lexema('куче', $first);
        $cat = $lexema('котка', null);
        $loose = $lexema('хляб', $second);

        $migration->up();

        $links = DB::table('exercise_lexema')
            ->whereIn('exercise_id', [$first, $second])
            ->get()
            ->map(fn ($row) => [(int) $row->exercise_id, (int) $row->lexema_id])
            ->all();

        $this->assertEqualsCanonicalizing([
            [$first, $dog],
            [$second, $dog],
            [$second, $cat],
            [$second, $loose],
        ], $links);
        $this->assertFalse(Schema::hasColumn('lexemas', 'exercise_id'));
    }

    public function test_rolling_back_restores_the_lowest_linked_exercise(): void
    {
        $first = $this->exercise(['Куче']);
        $second = $this->exercise(['Куче']);
        $dog = DB::table('lexemas')->insertGetId(['uuid' => (string) Str::uuid7(), 'word' => 'куче']);

        DB::table('exercise_lexema')->insert([
            ['exercise_id' => $first, 'lexema_id' => $dog],
            ['exercise_id' => $second, 'lexema_id' => $dog],
        ]);

        $migration = require database_path('migrations/2026_09_26_000001_create_exercise_lexema_table.php');
        $migration->down();

        $this->assertSame(min($first, $second), (int) DB::table('lexemas')->find($dog)->exercise_id);
        $this->assertFalse(Schema::hasTable('exercise_lexema'));
    }
}
