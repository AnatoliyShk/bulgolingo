<?php

namespace App\Enums;

use App\Rules\OptionIndex;
use App\Support\ClauseField;

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

    /**
     * The type's name in the given language. English is the default because
     * it is what the UI shows everywhere today.
     */
    public function getDescription(LanguageCode $language = LanguageCode::EN): string
    {
        return match ($language) {
            LanguageCode::EN => match ($this) {
                self::MULTIPLE_CHOICE => 'Multiple Choice',
                self::TRUE_FALSE => 'True/False',
                self::FILL_IN_THE_BLANK => 'Fill in the Blank',
                self::IMAGE_MATCHING => 'Image Matching',
            },
            LanguageCode::BG => match ($this) {
                self::MULTIPLE_CHOICE => 'Множествен избор',
                self::TRUE_FALSE => 'Вярно/Невярно',
                self::FILL_IN_THE_BLANK => 'Попълване на празното място',
                self::IMAGE_MATCHING => 'Съпоставяне на изображения',
            },
        };
    }

    /**
     * Value/label pairs for the exercise-type selects, built from the cases so
     * a type added here reaches every form without a second edit. Each also
     * carries the type's blank clause, which the admin exercise form starts a
     * new exercise from and fills an old one's missing fields with, so the
     * frontend never restates a clause shape of its own.
     *
     * @return array<int, array{value: string, label: string, clause: array<string, mixed>}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type) => [
                'value' => $type->value,
                'label' => $type->getDescription(),
                'clause' => $type->defaultClause(),
            ],
            self::cases()
        );
    }

    /**
     * The one definition of every type's clause: each key with its validation
     * rules, and each field an admin fills in with the value a blank clause
     * starts it at. dataRules() and defaultClause() are both read from here,
     * so changing a shape is a single edit.
     *
     * Answers start as a valid choice and lists at the size the form needs; a
     * word-pair clause opens at the minimum pair count and without an order,
     * which leaves the columns to be shuffled later.
     *
     * @return array<string, ClauseField>
     */
    private function clauseFields(): array
    {
        $explanation = ClauseField::withDefault('', ['required', 'string']);

        return match ($this) {
            self::MULTIPLE_CHOICE => [
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
                'explanation' => $explanation,
            ],
            self::TRUE_FALSE => [
                'sentence' => ClauseField::withDefault('', ['required', 'string']),
                'correct_option' => ClauseField::withDefault(true, ['required', 'boolean']),
                'explanation' => $explanation,
            ],
            self::FILL_IN_THE_BLANK => [
                'sentence' => ClauseField::withDefault('', ['required', 'string']),
                'options' => ClauseField::withDefault(['', '', '', ''], ['required', 'array']),
                'correct_option' => ClauseField::withDefault(0, ['required', 'integer', new OptionIndex]),
                'explanation' => $explanation,
            ],
            self::IMAGE_MATCHING => [
                'options' => ClauseField::withDefault(['', '', '', ''], ['required', 'array', 'min:2']),
                'options.*' => ClauseField::rulesOnly(['required', 'string']),
                'correct_option' => ClauseField::withDefault(0, ['required', 'integer', new OptionIndex]),
                'explanation' => $explanation,
            ],
        };
    }

    /**
     * The empty clause an admin fills in: every field clauseFields() gives a
     * blank value, in declaration order.
     *
     * @return array<string, mixed>
     */
    public function defaultClause(): array
    {
        return array_map(
            fn (ClauseField $field) => $field->default,
            array_filter($this->clauseFields(), fn (ClauseField $field) => $field->inBlankClause),
        );
    }

    /**
     * The clause's validation rules, keyed as in clauseFields().
     *
     * @return array<string, array<int, mixed>>
     */
    public function dataRules(): array
    {
        return array_map(fn (ClauseField $field) => $field->rules, $this->clauseFields());
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
