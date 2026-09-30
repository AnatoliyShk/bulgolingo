<?php

namespace App\Enums;

use App\Contracts\ExerciseDefinition;
use App\Models\Exercise;
use App\Models\FillInTheBlankExercise;
use App\Models\ImageMatchingExercise;
use App\Models\MultipleChoiceExercise;
use App\Models\TrueFalseExercise;

/**
 * The stored decision_type of an exercise. Each case has its own model on the
 * exercises table (modelClass()), and what the type means — its clause shape,
 * normalizing, labels and the rest — lives there; the methods here only
 * forward to a blank instance of it for callers that hold the enum.
 */
enum ExerciseType: string
{
    case MULTIPLE_CHOICE = 'multiple_choice';
    case TRUE_FALSE = 'true_false';
    case FILL_IN_THE_BLANK = 'fill_in_the_blank';

    case IMAGE_MATCHING = 'image_matching';

    /**
     * @return class-string<Exercise&ExerciseDefinition>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::MULTIPLE_CHOICE => MultipleChoiceExercise::class,
            self::TRUE_FALSE => TrueFalseExercise::class,
            self::FILL_IN_THE_BLANK => FillInTheBlankExercise::class,
            self::IMAGE_MATCHING => ImageMatchingExercise::class,
        };
    }

    /**
     * A blank model of this type, for asking what the type means without a
     * row: its label, clause shape, rules and whether it needs an image.
     */
    public function definition(): ExerciseDefinition
    {
        $class = $this->modelClass();

        return new $class;
    }

    /**
     * The type's name in the given language. English is the default because
     * it is what the UI shows everywhere today.
     */
    public function getDescription(LanguageCode $language = LanguageCode::EN): string
    {
        return $this->definition()->label($language);
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
     * @return array<string, mixed>
     */
    public function defaultClause(): array
    {
        return $this->definition()->defaultClause();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function dataRules(): array
    {
        return $this->definition()->clauseRules();
    }

    /**
     * Messages for dataRules(), keyed the same way. ExerciseObserver prefixes
     * both with "clause." before validating.
     */
    public function dataMessages(): array
    {
        return $this->definition()->clauseMessages();
    }
}
