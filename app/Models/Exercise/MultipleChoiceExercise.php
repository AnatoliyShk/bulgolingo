<?php

namespace App\Models\Exercise;

use App\Contracts\ExerciseDefinition;
use App\Enums\ExerciseType;
use App\Enums\LanguageCode;
use App\Models\Exercise;
use App\Support\ClauseField;

/**
 * Word-pair matching: the words of the exercise's language and their
 * translations dealt into two columns. Each pair is stored with its term in the
 * exercise's own language and its translation in translation_language_id, so
 * no language is named here; the clause shows a pair as [translation, term],
 * the translation being the first cell, the one on the left of the board. The
 * board order is stored with the pairs so every student sees the same layout
 * until an admin edits the exercise.
 */
final class MultipleChoiceExercise extends Exercise implements ExerciseDefinition
{
    use DefinesExerciseType;

    /**
     * Word-pair matching only works as a drill with enough words on the board:
     * at least 5 pairs, which is 10 words — 5 per language.
     */
    public const MIN_WORD_PAIRS = 5;

    protected const ?ExerciseType TYPE = ExerciseType::MULTIPLE_CHOICE;

    public function label(LanguageCode $language): string
    {
        return match ($language) {
            LanguageCode::EN => 'Multiple Choice',
            LanguageCode::BG => 'Множествен избор',
        };
    }

    /**
     * A blank clause opens at the minimum pair count and without an order,
     * which leaves the columns to be shuffled later.
     */
    public function clauseFields(): array
    {
        return [
            'pairs' => ClauseField::withDefault(
                array_fill(0, self::MIN_WORD_PAIRS, ['', '']),
                ['required', 'array', 'min:'.self::MIN_WORD_PAIRS],
            ),
            'pairs.*' => ClauseField::rulesOnly(['required', 'array', 'size:2']),
            'pairs.*.0' => ClauseField::rulesOnly(['required', 'string', 'distinct:ignore_case']),
            'pairs.*.1' => ClauseField::rulesOnly(['required', 'string', 'distinct:ignore_case']),
            'order' => ClauseField::rulesOnly(['sometimes', 'array:left,right']),
            'order.left' => ClauseField::rulesOnly(['sometimes', 'array']),
            'order.left.*' => ClauseField::rulesOnly(['integer', 'min:0', 'distinct']),
            'order.right' => ClauseField::rulesOnly(['sometimes', 'array']),
            'order.right.*' => ClauseField::rulesOnly(['integer', 'min:0', 'distinct']),
            'explanation' => $this->explanationField(),
        ];
    }

    public function clauseMessages(): array
    {
        return [
            'pairs.min' => 'A word pair exercise needs at least '.self::MIN_WORD_PAIRS.' pairs: 10 words, 5 per language.',
            'pairs.*.0.distinct' => 'Each word may only appear once in the first column.',
            'pairs.*.1.distinct' => 'Each translation may only appear once in the second column.',
        ];
    }

    /**
     * Repairs the stored column order so it describes the pairs actually
     * present: entries outside the pair range or repeated are dropped, and any
     * pair the order forgot is appended. That keeps an admin's shuffle usable
     * after pairs are added or removed. A clause with no order at all is left
     * alone, which leaves the player free to shuffle for itself.
     */
    public function normalizedClause(): array
    {
        $clause = $this->clause ?? [];

        if (! isset($clause['order'])) {
            return $clause;
        }

        $order = is_array($clause['order']) ? $clause['order'] : [];
        $count = self::pairCount($clause);

        $clause['order'] = [
            'left' => self::normalizeColumnOrder($order['left'] ?? [], $count),
            'right' => self::normalizeColumnOrder($order['right'] ?? [], $count),
        ];

        return $clause;
    }

    /**
     * Every admin save deals the board again, so an exercise that has just
     * been edited never comes back with the layout a student may have learned
     * by position. A save that changes nothing is not an update as far as
     * Eloquent is concerned and so leaves the order standing.
     */
    public function clauseAfterEdit(): array
    {
        $clause = $this->clause ?? [];
        $count = self::pairCount($clause);

        if ($count === 0) {
            return $clause;
        }

        $clause['order'] = self::shuffledOrder($count);

        return $clause;
    }

    /**
     * The explanation and the translation language go on the exercise's own
     * row; the pairs and their board order go to rows of their own.
     */
    public function clauseColumns(array $clause): array
    {
        return $this->scalarColumns($clause, translated: true);
    }

    /**
     * One row per pair, the clause's [translation, term] cells split into the
     * two columns, with the stored board order turned into each pair's place
     * in the left and right column. A clause with no order leaves both places
     * null, which leaves the player to shuffle.
     */
    public function storeClauseRows(array $clause): void
    {
        $left = self::columnPlaces($clause['order']['left'] ?? null);
        $right = self::columnPlaces($clause['order']['right'] ?? null);

        $this->replaceRows(pairs: collect($clause['pairs'] ?? [])
            ->values()
            ->map(fn (array $pair, int $position) => [
                'exercise_id' => $this->getKey(),
                'position' => $position,
                'term' => $pair[1],
                'translation' => $pair[0],
                'left_position' => $left[$position] ?? null,
                'right_position' => $right[$position] ?? null,
            ])
            ->all());
    }

    /**
     * The pairs and, when every pair has a place in both columns, the board
     * order they describe; without one the order key is left out.
     */
    public function storedClause(): array
    {
        $pairs = $this->wordPairs;
        $clause = ['pairs' => $pairs->map(fn ($pair) => [$pair->translation, $pair->term])->all()];

        if ($pairs->isNotEmpty() && $pairs->every(fn ($pair) => $pair->left_position !== null && $pair->right_position !== null)) {
            $clause['order'] = [
                'left' => $pairs->sortBy('left_position')->pluck('position')->values()->all(),
                'right' => $pairs->sortBy('right_position')->pluck('position')->values()->all(),
            ];
        }

        $clause['explanation'] = $this->explanation;

        return $clause;
    }

    /**
     * The pairs as "word = translation", the board order left out since it
     * means nothing to the embedding model.
     */
    public function embeddingFields(): array
    {
        return [
            'Word pairs' => collect($this->clause['pairs'] ?? [])
                ->filter(fn ($pair) => is_array($pair))
                ->map(fn (array $pair) => implode(' = ', array_filter($pair, 'is_string')))
                ->implode('; '),
        ];
    }

    /**
     * Deals both columns again. The two sides are shuffled independently, and
     * an identical pair of permutations is rejected because that lays every
     * word opposite its own translation and gives the drill away.
     *
     * @return array{left: array<int, int>, right: array<int, int>}
     */
    public static function shuffledOrder(int $count): array
    {
        $left = self::shuffledIndices($count);
        $right = self::shuffledIndices($count);

        while ($count > 1 && $right === $left) {
            $right = self::shuffledIndices($count);
        }

        return ['left' => $left, 'right' => $right];
    }

    private static function shuffledIndices(int $count): array
    {
        $indices = $count > 0 ? range(0, $count - 1) : [];
        shuffle($indices);

        return $indices;
    }

    /**
     * For a column order given as the pair indexes top to bottom, each pair's
     * place in the column, keyed by pair index; empty when there is no order.
     *
     * @return array<int, int>
     */
    private static function columnPlaces(mixed $order): array
    {
        return is_array($order) ? array_flip(array_values($order)) : [];
    }

    private static function pairCount(array $clause): int
    {
        return is_array($clause['pairs'] ?? null) ? count($clause['pairs']) : 0;
    }

    /**
     * Turns one stored column order into a permutation of 0..$count-1, keeping
     * the positions it already gets right and appending whatever is missing.
     */
    private static function normalizeColumnOrder(mixed $order, int $count): array
    {
        $all = $count > 0 ? range(0, $count - 1) : [];

        $kept = collect(is_array($order) ? $order : [])
            ->filter(fn ($index) => is_int($index) || (is_string($index) && ctype_digit($index)))
            ->map(fn ($index) => (int) $index)
            ->filter(fn ($index) => $index >= 0 && $index < $count)
            ->unique()
            ->values();

        return $kept->merge(collect($all)->diff($kept))->values()->all();
    }
}
