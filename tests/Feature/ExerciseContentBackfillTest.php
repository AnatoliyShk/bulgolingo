<?php

namespace Tests\Feature;

use App\Models\Language;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * The backfill migration turns every legacy `clause` JSON into the columns and
 * rows that replaced it. It is rolled back first so exercises can be written
 * the way production held them, through the query builder with only a clause,
 * and then run forward. Postgres runs the DDL inside the test's transaction, so
 * the schema is restored when it ends.
 */
class ExerciseContentBackfillTest extends TestCase
{
    use DatabaseTransactions;

    private const string MIGRATION = 'migrations/2026_10_02_000006_backfill_exercise_content_from_clause.php';

    private function backfill(): void
    {
        (require database_path(self::MIGRATION))->up();
    }

    private function legacy(string $type, array $clause): int
    {
        return DB::table('exercises')->insertGetId([
            'uuid' => (string) Str::uuid7(),
            'name' => 'Legacy',
            'decision_type' => $type,
            'clause' => json_encode($clause),
        ]);
    }

    private function inPath(int $exerciseId, string $language): void
    {
        $pathId = DB::table('learning_paths')->insertGetId(['uuid' => (string) Str::uuid7(), 'name' => 'P', 'language' => $language]);
        $lessonId = DB::table('lessons')->insertGetId(['uuid' => (string) Str::uuid7(), 'name' => 'L', 'description' => 'D']);
        DB::table('learning_path_lesson')->insert(['learning_path_id' => $pathId, 'lesson_id' => $lessonId]);
        DB::table('exercise_lesson')->insert(['lesson_id' => $lessonId, 'exercise_id' => $exerciseId, 'order' => 0]);
    }

    private function pairs(int $exerciseId): array
    {
        return DB::table('exercise_word_pairs')->where('exercise_id', $exerciseId)->orderBy('position')->get()
            ->map(fn ($row) => [$row->translation, $row->term, $row->left_position, $row->right_position])
            ->all();
    }

    protected function setUp(): void
    {
        parent::setUp();

        (require database_path(self::MIGRATION))->down();
    }

    public function test_pairs_in_either_order_are_oriented_by_the_script_of_the_exercises_language(): void
    {
        $englishFirst = $this->legacy('multiple_choice', ['pairs' => [['Bread', 'Хляб'], ['Water', 'Вода']], 'explanation' => 'e']);
        $bulgarianFirst = $this->legacy('multiple_choice', ['pairs' => [['хляб', 'bread'], ['вода', 'water']], 'explanation' => 'e']);

        $this->backfill();

        $this->assertSame([['Bread', 'Хляб', null, null], ['Water', 'Вода', null, null]], $this->pairs($englishFirst));
        $this->assertSame([['bread', 'хляб', null, null], ['water', 'вода', null, null]], $this->pairs($bulgarianFirst));
    }

    public function test_one_exercise_can_mix_both_orders(): void
    {
        $id = $this->legacy('multiple_choice', ['pairs' => [['Bread', 'Хляб'], ['вода', 'Water']], 'explanation' => 'e']);

        $this->backfill();

        $this->assertSame([['Bread', 'Хляб', null, null], ['Water', 'вода', null, null]], $this->pairs($id));
    }

    /**
     * Digits belong to no script, so a pair of them cannot say which cell is
     * the term; the pair takes the second cell, the order the player and the
     * seeders use, which keeps it reading exactly as it was stored.
     */
    public function test_a_pair_that_does_not_decide_takes_the_second_cell_as_its_term(): void
    {
        $id = $this->legacy('multiple_choice', ['pairs' => [['1', '10'], ['2', '20']], 'explanation' => 'e']);

        $this->backfill();

        $this->assertSame([['1', '10', null, null], ['2', '20', null, null]], $this->pairs($id));
    }

    public function test_an_undecided_pair_follows_the_orientation_of_the_pairs_around_it(): void
    {
        $id = $this->legacy('multiple_choice', ['pairs' => [['хляб', 'bread'], ['1', '10'], ['вода', 'water']], 'explanation' => 'e']);

        $this->backfill();

        $this->assertSame([['bread', 'хляб', null, null], ['10', '1', null, null], ['water', 'вода', null, null]], $this->pairs($id));
    }

    public function test_the_board_order_becomes_a_place_in_each_column(): void
    {
        $id = $this->legacy('multiple_choice', [
            'pairs' => [['a', 'а'], ['b', 'б'], ['c', 'в'], ['d', 'г'], ['e', 'д']],
            'order' => ['left' => [2, 0, 1, 4, 3], 'right' => [1, 3, 0, 2, 4]],
            'explanation' => 'e',
        ]);

        $this->backfill();

        $this->assertSame([1, 2, 0, 4, 3], array_column($this->pairs($id), 2));
        $this->assertSame([2, 0, 3, 1, 4], array_column($this->pairs($id), 3));
    }

    public function test_a_board_order_that_is_not_a_permutation_is_dropped(): void
    {
        $id = $this->legacy('multiple_choice', [
            'pairs' => [['a', 'а'], ['b', 'б'], ['c', 'в']],
            'order' => ['left' => [0, 0, 1], 'right' => [2, 1, 0]],
            'explanation' => 'e',
        ]);

        $this->backfill();

        $this->assertSame([null, null, null], array_column($this->pairs($id), 2));
        $this->assertSame([null, null, null], array_column($this->pairs($id), 3));
    }

    public function test_an_exercise_teaches_the_language_of_its_learning_path_and_translates_into_another(): void
    {
        $bulgarian = $this->legacy('multiple_choice', ['pairs' => [['Bread', 'Хляб']], 'explanation' => 'e']);
        $english = $this->legacy('multiple_choice', ['pairs' => [['Хляб', 'Bread']], 'explanation' => 'e']);
        $loose = $this->legacy('fill_in_the_blank', ['sentence' => 's', 'options' => ['a', 'b'], 'correct_option' => 0, 'explanation' => 'e']);
        $this->inPath($bulgarian, 'BG');
        $this->inPath($english, 'en');

        $this->backfill();

        $row = fn (int $id) => DB::table('exercises')->find($id);
        $this->assertSame(Language::idFor('bg'), $row($bulgarian)->language_id);
        $this->assertSame(Language::idFor('en'), $row($bulgarian)->translation_language_id);
        $this->assertSame(Language::idFor('en'), $row($english)->language_id);
        $this->assertSame(Language::idFor('bg'), $row($english)->translation_language_id);
        $this->assertSame(Language::defaultId(), $row($loose)->language_id);
        $this->assertNull($row($loose)->translation_language_id);
        $this->assertSame([['Хляб', 'Bread', null, null]], $this->pairs($english));
    }

    public function test_options_keep_their_positions_and_the_answer(): void
    {
        $id = $this->legacy('fill_in_the_blank', ['sentence' => 'Аз ___ студент', 'options' => ['съм', 'си', 'е', 'сме'], 'correct_option' => 2, 'explanation' => 'e']);

        $this->backfill();

        $this->assertSame('Аз ___ студент', DB::table('exercises')->find($id)->prompt);
        $this->assertSame(
            [['съм', false], ['си', false], ['е', true], ['сме', false]],
            DB::table('exercise_options')->where('exercise_id', $id)->orderBy('position')->get()->map(fn ($o) => [$o->text, (bool) $o->is_correct])->all()
        );
    }

    public function test_true_false_and_image_matching_keep_their_scalar_fields(): void
    {
        $trueFalse = $this->legacy('true_false', ['sentence' => 'Софията е столица', 'correct_option' => false, 'explanation' => 'No.']);
        $image = $this->legacy('image_matching', ['options' => ['куче', 'котка'], 'correct_option' => 1, 'explanation' => 'A cat.']);

        $this->backfill();

        $row = DB::table('exercises')->find($trueFalse);
        $this->assertFalse((bool) $row->statement_is_true);
        $this->assertSame(['Софията е столица', 'No.'], [$row->prompt, $row->explanation]);
        $this->assertSame('A cat.', DB::table('exercises')->find($image)->explanation);
        $this->assertSame(1, DB::table('exercise_options')->where('exercise_id', $image)->where('is_correct', true)->value('position'));
    }

    public function test_a_word_repeated_within_a_column_stops_the_migration_naming_the_exercise(): void
    {
        $id = $this->legacy('multiple_choice', ['pairs' => [['a', 'А'], ['b', 'а']], 'explanation' => 'e']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Exercise {$id} repeats a word");

        $this->backfill();
    }
}
