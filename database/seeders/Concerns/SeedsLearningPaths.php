<?php

namespace Database\Seeders\Concerns;

use App\Enums\ExerciseType;
use App\Enums\LanguageLevel;
use App\Enums\LearningPathType;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Lesson;

trait SeedsLearningPaths
{
    /**
     * Lessons are looked up inside this path rather than by name across the
     * table, so a lesson that shares a name with one in another path never
     * pulls that path's exercises in. Lessons run in id order and exercises in
     * pivot order, so the order of $lessons is the study order, and a second
     * run fills gaps instead of duplicating anything.
     *
     * @param  array<int, array{name: string, description: string, exercises: array<int, array>}>  $lessons
     */
    protected function seedPath(string $name, LanguageLevel $level, array $lessons): void
    {
        $path = LearningPath::firstOrCreate(
            ['name' => $name],
            ['language' => 'BG', 'type' => LearningPathType::Regular, 'level' => $level],
        );

        foreach ($lessons as $lessonData) {
            $lesson = $path->lessons()->where('lessons.name', $lessonData['name'])->first()
                ?? Lesson::create([
                    'name' => $lessonData['name'],
                    'description' => $lessonData['description'],
                ]);

            $path->lessons()->syncWithoutDetaching([$lesson->id]);

            foreach ($lessonData['exercises'] as $exercise) {
                $model = $lesson->exercises()->where('name', $exercise['name'])->first()
                    ?? Exercise::create($exercise);

                $lesson->attachExerciseAtEnd($model);
            }
        }
    }

    /**
     * A word-pair board. The explanation doubles as the player's prompt.
     *
     * @param  array<int, array{0: string, 1: string}>  $pairs
     */
    protected static function pairs(string $name, string $explanation, array $pairs): array
    {
        return [
            'name' => $name,
            'decision_type' => ExerciseType::MULTIPLE_CHOICE,
            'clause' => ['pairs' => $pairs, 'explanation' => $explanation],
        ];
    }

    /**
     * The player finds the gap by splitting the sentence on whitespace, so
     * "__" has to stand alone with a space on each side.
     *
     * @param  array<int, string>  $options
     */
    protected static function fill(string $name, string $sentence, array $options, int $correct, string $explanation): array
    {
        return [
            'name' => $name,
            'decision_type' => ExerciseType::FILL_IN_THE_BLANK,
            'clause' => [
                'sentence' => $sentence,
                'options' => $options,
                'correct_option' => $correct,
                'explanation' => $explanation,
            ],
        ];
    }

    protected static function trueFalse(string $name, string $sentence, bool $correct, string $explanation): array
    {
        return [
            'name' => $name,
            'decision_type' => ExerciseType::TRUE_FALSE,
            'clause' => [
                'sentence' => $sentence,
                'correct_option' => $correct,
                'explanation' => $explanation,
            ],
        ];
    }
}
