<?php

namespace Database\Seeders\Concerns;

use App\Enums\ExerciseType;
use App\Enums\LanguageLevel;
use App\Enums\LearningPathType;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Lesson;
use InvalidArgumentException;

trait SeedsLearningPaths
{
    /**
     * Seeds every path file in the directory in filename order, which is why
     * the files carry a numeric prefix: paths are listed in id order, so the
     * prefix decides where each one lands in the catalog.
     */
    protected function seedPathsFrom(string $directory): void
    {
        $files = glob($directory.'/*.json');

        if (! $files) {
            throw new InvalidArgumentException("No learning path files found in {$directory}.");
        }

        sort($files);

        foreach ($files as $file) {
            $this->seedPathFile($file);
        }
    }

    /**
     * A path file holds the path's name and level and its lessons in study
     * order. Each exercise is an object with a single key, the ExerciseType
     * value, whose value is the exercise's name plus its clause, so a file
     * reads as the list of exercises a student will see.
     */
    protected function seedPathFile(string $file): void
    {
        $path = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);

        $lessons = array_map(fn (array $lesson) => [
            'name' => $lesson['name'],
            'description' => $lesson['description'],
            'exercises' => array_map(
                fn (array $entry) => self::exerciseFromEntry($entry, $file),
                $lesson['exercises']
            ),
        ], $path['lessons']);

        $this->seedPath($path['name'], LanguageLevel::from($path['level']), $lessons);
    }

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
     * Turns one {"<type>": {name, ...clause}} entry into Exercise attributes.
     * An entry with more or fewer than one key is refused rather than guessed
     * at, and an unknown type fails in ExerciseType::from(); the clause itself
     * is validated by ExerciseObserver when the exercise is created.
     *
     * @return array{name: string, decision_type: ExerciseType, clause: array}
     */
    private static function exerciseFromEntry(array $entry, string $file): array
    {
        if (count($entry) !== 1) {
            throw new InvalidArgumentException('Each exercise in '.basename($file).' must have exactly one type key, got: '.implode(', ', array_keys($entry)));
        }

        $type = ExerciseType::from(array_key_first($entry));
        $clause = $entry[$type->value];
        $name = $clause['name'];
        unset($clause['name']);

        return ['name' => $name, 'decision_type' => $type, 'clause' => $clause];
    }
}
