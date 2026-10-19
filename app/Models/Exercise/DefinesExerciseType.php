<?php

namespace App\Models\Exercise;

use App\Models\ExerciseOption;
use App\Models\ExerciseWordPair;
use App\Models\Language;
use App\Support\ClauseField;

/**
 * The part of ExerciseDefinition most exercise types share: the blank clause
 * and rules read from the type's clauseFields(), and no messages, normalizing,
 * edit hook, answer words, image or child rows of their own, plus the clause
 * readers and the storage helpers the types build on. A type model uses it and
 * overrides only what makes it different; label(), clauseFields(),
 * storedClause() and embeddingFields() it always defines itself.
 */
trait DefinesExerciseType
{
    public function defaultClause(): array
    {
        return array_map(
            fn (ClauseField $field) => $field->default,
            array_filter($this->clauseFields(), fn (ClauseField $field) => $field->inBlankClause),
        );
    }

    public function clauseRules(): array
    {
        return array_map(fn (ClauseField $field) => $field->rules, $this->clauseFields());
    }

    public function clauseMessages(): array
    {
        return [];
    }

    public function requiresImage(): bool
    {
        return false;
    }

    public function normalizedClause(): array
    {
        return $this->clause ?? [];
    }

    public function clauseAfterEdit(): array
    {
        return $this->clause ?? [];
    }

    public function answerWords(): array
    {
        return [];
    }

    public function clauseColumns(array $clause): array
    {
        return $this->scalarColumns($clause);
    }

    public function storeClauseRows(array $clause): void
    {
        $this->replaceRows();
    }

    protected function explanationField(): ClauseField
    {
        return ClauseField::withDefault('', ['required', 'string']);
    }

    /**
     * The scalar columns every type sets, with the prompt taken from the
     * clause's sentence. A type with a true/false answer passes it, and a type
     * with a language pair asks for its translation language, which defaults
     * from the exercise's own language when the exercise has none yet.
     *
     * @return array<string, mixed>
     */
    protected function scalarColumns(array $clause, ?bool $statementIsTrue = null, bool $translated = false): array
    {
        return [
            'prompt' => is_string($clause['sentence'] ?? null) ? $clause['sentence'] : null,
            'explanation' => (string) ($clause['explanation'] ?? ''),
            'statement_is_true' => $statementIsTrue,
            'translation_language_id' => $translated
                ? ($this->translation_language_id ?? Language::defaultTranslationIdFor($this->language_id))
                : null,
        ];
    }

    /**
     * Replaces every option and word pair of the exercise with the given rows,
     * each already carrying the exercise's id.
     *
     * @param  array<int, array<string, mixed>>  $options
     * @param  array<int, array<string, mixed>>  $pairs
     */
    protected function replaceRows(array $options = [], array $pairs = []): void
    {
        ExerciseOption::query()->where('exercise_id', $this->getKey())->delete();
        ExerciseWordPair::query()->where('exercise_id', $this->getKey())->delete();

        if ($options !== []) {
            ExerciseOption::query()->insert($options);
        }

        if ($pairs !== []) {
            ExerciseWordPair::query()->insert($pairs);
        }
    }

    /**
     * One option row per entry of the clause's options, in order, the one at
     * correct_option marked correct. An entry that is not text is stored as an
     * empty option so that every later position keeps its place.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function optionRows(array $clause): array
    {
        return collect($clause['options'] ?? [])
            ->values()
            ->map(fn ($text, int $position) => [
                'exercise_id' => $this->getKey(),
                'position' => $position,
                'text' => is_scalar($text) ? (string) $text : '',
                'is_correct' => $position === ($clause['correct_option'] ?? null),
            ])
            ->all();
    }

    /**
     * The clause's `options` and `correct_option` read back from the option
     * rows; correct_option is null when no row is marked correct.
     *
     * @return array{options: array<int, string>, correct_option: ?int}
     */
    protected function storedOptions(): array
    {
        return [
            'options' => $this->options->pluck('text')->all(),
            'correct_option' => $this->options->firstWhere('is_correct', true)?->position,
        ];
    }

    /**
     * The clause with a numeric correct_option typed as the int index the
     * players compare with ===. An admin edit that carries an image goes out
     * as multipart, where every field arrives as a string, and the integer
     * rule accepts it as it stands, so without this an exercise saved
     * alongside a picture would store "2" and refuse its own correct answer.
     * A value that is not a number is left for validation to reject.
     */
    protected function clauseWithIndexAnswer(): array
    {
        $clause = $this->clause ?? [];

        if (array_key_exists('correct_option', $clause) && is_numeric($clause['correct_option'])) {
            $clause['correct_option'] = (int) $clause['correct_option'];
        }

        return $clause;
    }

    protected function optionsLine(): string
    {
        return collect($this->clause['options'] ?? [])
            ->filter(fn ($option) => is_string($option))
            ->implode(', ');
    }

    /**
     * The option correct_option points at, or null when it points nowhere.
     */
    protected function chosenOption(): ?string
    {
        $index = $this->clause['correct_option'] ?? null;
        $option = is_int($index) ? ($this->clause['options'][$index] ?? null) : null;

        return is_string($option) ? $option : null;
    }
}
