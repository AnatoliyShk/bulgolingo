<?php

namespace App\Enums;

enum ExerciseType: string
{
    /**
     * Word-pair matching only works as a drill with enough words on the board:
     * at least 5 pairs, which is 10 words — 5 per language.
     */
    public const MIN_WORD_PAIRS = 5;

    case MULTIPLE_CHOICE = 'multiple_choice';
    case TRUE_FALSE = 'true_false';
    case FILL_IN_THE_BLANK = 'fill_in_the_blank';

    case IMAGE_MATCHING = 'image_matching';

    public function getDescription(): string
    {
        return match ($this) {
            self::MULTIPLE_CHOICE => 'Multiple Choice',
            self::TRUE_FALSE => 'True/False',
            self::FILL_IN_THE_BLANK => 'Fill in the Blank',
            self::IMAGE_MATCHING => 'Image Matching',
        };
    }

    public function dataRules(): array
    {
        return match ($this) {
            self::MULTIPLE_CHOICE => [
                'pairs' => ['required', 'array', 'min:'.self::MIN_WORD_PAIRS],
                'pairs.*' => ['required', 'array', 'size:2'],
                'pairs.*.0' => ['required', 'string', 'distinct:ignore_case'],
                'pairs.*.1' => ['required', 'string', 'distinct:ignore_case'],
                'order' => ['sometimes', 'array:left,right'],
                'order.left' => ['sometimes', 'array'],
                'order.left.*' => ['integer', 'min:0', 'distinct'],
                'order.right' => ['sometimes', 'array'],
                'order.right.*' => ['integer', 'min:0', 'distinct'],
                'explanation' => ['required', 'string'],
            ],
            self::TRUE_FALSE => [
                'sentence' => ['required', 'string'],
                'correct_option' => ['required', 'boolean'],
                'explanation' => ['required', 'string'],
            ],
            self::FILL_IN_THE_BLANK => [
                'sentence' => ['required', 'string'],
                'options' => ['required', 'array'],
                'correct_option' => ['required', 'integer'],
                'explanation' => ['required', 'string'],
            ],
            self::IMAGE_MATCHING => [
                'options' => ['required', 'array', 'min:2'],
                'options.*' => ['required', 'string'],
                'correct_option' => ['required', 'integer'],
                'explanation' => ['required', 'string'],
            ],
            default => [],
        };
    }

    /**
     * Messages for the clause rules above, keyed the same way as dataRules().
     * ExerciseObserver prefixes both with "clause." before validating.
     */
    public function dataMessages(): array
    {
        return match ($this) {
            self::MULTIPLE_CHOICE => [
                'pairs.min' => 'A word pair exercise needs at least '.self::MIN_WORD_PAIRS.' pairs: 10 words, 5 per language.',
                'pairs.*.0.distinct' => 'Each word may only appear once in the first column.',
                'pairs.*.1.distinct' => 'Each translation may only appear once in the second column.',
            ],
            default => [],
        };
    }

    /**
     * Deals both columns again. The two sides are shuffled independently, and
     * an identical pair of permutations is rejected because that lays every
     * word opposite its own translation and gives the drill away.
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
     * Settles a clause into the types the players expect before it is stored.
     *
     * The answer is always typed. On a word-pair clause the stored column
     * order is also repaired so it describes the pairs actually present:
     * entries outside the pair range or repeated are dropped, and any pair the
     * order forgot is appended. That keeps an admin's shuffle usable after
     * pairs are added or removed. A clause with no order at all is left alone,
     * which leaves the player free to shuffle for itself.
     */
    public function normalizeClause(array $clause): array
    {
        $clause = $this->normalizeCorrectOption($clause);

        if ($this !== self::MULTIPLE_CHOICE || ! isset($clause['order'])) {
            return $clause;
        }

        $order = is_array($clause['order']) ? $clause['order'] : [];
        $count = is_array($clause['pairs'] ?? null) ? count($clause['pairs']) : 0;

        $clause['order'] = [
            'left' => self::normalizeColumnOrder($order['left'] ?? [], $count),
            'right' => self::normalizeColumnOrder($order['right'] ?? [], $count),
        ];

        return $clause;
    }

    /**
     * Types the stored answer, which the players compare with === and so lose
     * to a numeric string. An admin edit that carries an image goes out as
     * multipart, where every field arrives as a string, and both the integer
     * and boolean rules accept those strings as they stand — so an exercise
     * saved alongside a picture would otherwise store "2" where the seeder
     * stored 2 and refuse its own correct answer. A value that is not a number
     * at all is left untouched for validation to reject.
     */
    private function normalizeCorrectOption(array $clause): array
    {
        if (! array_key_exists('correct_option', $clause)) {
            return $clause;
        }

        $value = $clause['correct_option'];

        $clause['correct_option'] = match ($this) {
            self::TRUE_FALSE => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $value,
            self::FILL_IN_THE_BLANK, self::IMAGE_MATCHING => is_numeric($value) ? (int) $value : $value,
            default => $value,
        };

        return $clause;
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
