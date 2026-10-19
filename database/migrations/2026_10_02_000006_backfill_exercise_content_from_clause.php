<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const int CHUNK = 500;

    /**
     * Explodes every exercise's `clause` JSON into the language columns, the
     * scalar columns and the `exercise_options` / `exercise_word_pairs` rows,
     * then makes `language_id` required and puts the new constraints on.
     *
     * An exercise teaches the language of the first learning path it sits in,
     * matched against `languages.code`, and Bulgarian when it sits in none or
     * the path's text names no known language: that is what all the content so
     * far is. A word-pair exercise translates into English, or into Bulgarian
     * when it already teaches English.
     *
     * The pairs were stored as [a, b] arrays without saying which cell was the
     * language being taught, and the seeded data has both orders, so each pair
     * is oriented by script: the cell written in the exercise language's
     * script is the term. A pair that does not decide it (both or neither cell
     * match) takes the orientation most of the exercise's other pairs have,
     * and the second cell when none decides, which is the order the player
     * and the seeders use. The pairs are written back to the clause shape as
     * [translation, term], so a pair already in that order reads exactly as it
     * did.
     *
     * A stored board order is kept only when each column is a full permutation
     * of the pairs; anything else is dropped and the player shuffles. A
     * duplicated word within a column stops the migration with the exercise
     * ids, since the unique index that follows would reject it anyway and
     * silently dropping a pair would change what a student is taught.
     */
    public function up(): void
    {
        $languages = DB::table('languages')->get()->keyBy('code');
        $bulgarian = $languages['bg'];
        $english = $languages['en'];

        DB::table('exercises')->orderBy('id')->chunkById(self::CHUNK, function (Collection $exercises) use ($languages, $bulgarian, $english) {
            $pathLanguages = $this->pathLanguageByExercise($exercises->pluck('id')->all());
            $options = [];
            $pairs = [];

            foreach ($exercises as $exercise) {
                $clause = json_decode((string) $exercise->clause, true) ?: [];
                $code = $pathLanguages[$exercise->id] ?? null;
                $taught = $languages[$code] ?? $bulgarian;
                $translation = $exercise->decision_type === 'multiple_choice'
                    ? ($taught->id === $english->id ? $bulgarian : $english)
                    : null;

                DB::table('exercises')->where('id', $exercise->id)->update([
                    'language_id' => $taught->id,
                    'translation_language_id' => $translation?->id,
                    'prompt' => $clause['sentence'] ?? null,
                    'explanation' => (string) ($clause['explanation'] ?? ''),
                    'statement_is_true' => $exercise->decision_type === 'true_false' ? (bool) ($clause['correct_option'] ?? false) : null,
                ]);

                array_push($options, ...$this->optionRows($exercise->id, $clause));
                array_push($pairs, ...$this->pairRows($exercise->id, $clause, $taught->unicode_script));
            }

            foreach (array_chunk($options, 1000) as $rows) {
                DB::table('exercise_options')->insert($rows);
            }

            foreach (array_chunk($pairs, 1000) as $rows) {
                DB::table('exercise_word_pairs')->insert($rows);
            }
        });

        Schema::table('exercises', function (Blueprint $table) {
            $table->foreignId('language_id')->nullable(false)->change();
        });

        DB::statement('CREATE UNIQUE INDEX exercise_word_pairs_term_unique ON exercise_word_pairs (exercise_id, lower(term))');
        DB::statement('CREATE UNIQUE INDEX exercise_word_pairs_translation_unique ON exercise_word_pairs (exercise_id, lower(translation))');

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("ALTER TABLE exercises ADD CONSTRAINT exercises_word_pair_translation_language_check CHECK (decision_type <> 'multiple_choice' OR translation_language_id IS NOT NULL)");
        DB::statement("ALTER TABLE exercises ADD CONSTRAINT exercises_true_false_statement_check CHECK (decision_type <> 'true_false' OR statement_is_true IS NOT NULL)");
    }

    /**
     * The language code of the first learning path (lowest id) each exercise
     * sits in, lower-cased, keyed by exercise id.
     *
     * @param  array<int, int>  $exerciseIds
     * @return array<int, string>
     */
    private function pathLanguageByExercise(array $exerciseIds): array
    {
        return DB::table('exercise_lesson as el')
            ->join('learning_path_lesson as lpl', 'lpl.lesson_id', '=', 'el.lesson_id')
            ->join('learning_paths as lp', 'lp.id', '=', 'lpl.learning_path_id')
            ->whereIn('el.exercise_id', $exerciseIds)
            ->orderByDesc('lp.id')
            ->get(['el.exercise_id', 'lp.language'])
            ->mapWithKeys(fn ($row) => [$row->exercise_id => mb_strtolower((string) $row->language)])
            ->all();
    }

    /**
     * One row per entry of the clause's options, in order, the one at
     * correct_option marked correct. A non-text entry becomes an empty option
     * so that every later position, and so the answer's index, is kept.
     *
     * @return array<int, array<string, mixed>>
     */
    private function optionRows(int $exerciseId, array $clause): array
    {
        $correct = is_numeric($clause['correct_option'] ?? null) ? (int) $clause['correct_option'] : null;

        return collect($clause['options'] ?? [])
            ->values()
            ->map(fn ($text, int $position) => [
                'exercise_id' => $exerciseId,
                'position' => $position,
                'text' => is_scalar($text) ? (string) $text : '',
                'is_correct' => $position === $correct,
            ])
            ->all();
    }

    /**
     * One row per word pair, each oriented into term and translation (see up()),
     * with the board order turned into per-pair column positions.
     *
     * @return array<int, array<string, mixed>>
     */
    private function pairRows(int $exerciseId, array $clause, string $script): array
    {
        $pairs = collect($clause['pairs'] ?? [])
            ->values()
            ->map(fn ($pair) => [(string) ($pair[0] ?? ''), (string) ($pair[1] ?? '')]);

        if ($pairs->isEmpty()) {
            return [];
        }

        $isTerm = fn (string $text) => preg_match('/\p{'.$script.'}/u', $text) === 1;

        $termSide = $pairs->map(function (array $pair) use ($isTerm) {
            [$first, $second] = [$isTerm($pair[0]), $isTerm($pair[1])];

            return $first === $second ? null : ($first ? 0 : 1);
        });

        $decided = $termSide->filter(fn ($side) => $side !== null);
        $fallback = $decided->isEmpty() ? 1 : ($decided->filter(fn ($side) => $side === 0)->count() > $decided->count() / 2 ? 0 : 1);

        $oriented = $pairs->map(function (array $pair, int $position) use ($termSide, $fallback) {
            $side = $termSide[$position] ?? $fallback;

            return ['term' => $pair[$side], 'translation' => $pair[1 - $side]];
        });

        $this->assertDistinct($exerciseId, $oriented->pluck('term'));
        $this->assertDistinct($exerciseId, $oriented->pluck('translation'));

        $left = $this->columnPositions($clause['order']['left'] ?? null, $pairs->count());
        $right = $this->columnPositions($clause['order']['right'] ?? null, $pairs->count());
        $ordered = $left !== null && $right !== null;

        return $oriented
            ->map(fn (array $pair, int $position) => [
                'exercise_id' => $exerciseId,
                'position' => $position,
                'term' => $pair['term'],
                'translation' => $pair['translation'],
                'left_position' => $ordered ? $left[$position] : null,
                'right_position' => $ordered ? $right[$position] : null,
            ])
            ->all();
    }

    /**
     * For a column order given as the pair indexes top to bottom, each pair's
     * place in the column; null unless the order is a permutation of the pairs.
     *
     * @return array<int, int>|null
     */
    private function columnPositions(mixed $order, int $count): ?array
    {
        if (! is_array($order) || count($order) !== $count) {
            return null;
        }

        $positions = [];

        foreach (array_values($order) as $place => $pair) {
            if (! is_int($pair) || $pair < 0 || $pair >= $count || isset($positions[$pair])) {
                return null;
            }

            $positions[$pair] = $place;
        }

        return $positions;
    }

    /**
     * @param  Collection<int, string>  $words
     */
    private function assertDistinct(int $exerciseId, Collection $words): void
    {
        $lowered = $words->map(fn (string $word) => mb_strtolower($word));

        if ($lowered->count() !== $lowered->unique()->count()) {
            throw new RuntimeException("Exercise {$exerciseId} repeats a word within one column of its word pairs; fix it before migrating.");
        }
    }

    /**
     * Reverse the migrations. The rows and columns this filled go with the
     * migrations that created them; what is removed here is only what this one
     * added.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS exercise_word_pairs_term_unique');
        DB::statement('DROP INDEX IF EXISTS exercise_word_pairs_translation_unique');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE exercises DROP CONSTRAINT IF EXISTS exercises_word_pair_translation_language_check');
            DB::statement('ALTER TABLE exercises DROP CONSTRAINT IF EXISTS exercises_true_false_statement_check');
        }

        Schema::table('exercises', function (Blueprint $table) {
            $table->foreignId('language_id')->nullable()->change();
        });
    }
};
