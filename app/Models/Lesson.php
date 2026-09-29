<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

#[Table('lessons')]
#[Fillable(['name', 'description'])]
class Lesson extends Model
{
    use HasUuidV7;

    /**
     * Every lesson as {id, name}, alphabetical, for the admin exercise list's
     * lesson pickers.
     *
     * @return Collection<int, Lesson>
     */
    public static function pickerOptions(): Collection
    {
        return static::orderBy('name')->get(['id', 'name']);
    }

    /**
     * Ordered by the pivot's `order`, the sequence students complete them in.
     */
    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class, 'exercise_lesson')
            ->using(ExerciseLesson::class)
            ->withPivot('order')
            ->orderBy('exercise_lesson.order');
    }

    /**
     * Appends the exercise to the lesson's order; no-op if already attached.
     * The lesson row is locked first, so two exercises appended at once cannot
     * both read the same last position and trip the (lesson_id, order) unique
     * index; the second waits and then reads the first one's position.
     */
    public function attachExerciseAtEnd(Exercise $exercise): void
    {
        DB::transaction(function () use ($exercise) {
            static::query()->whereKey($this->getKey())->lockForUpdate()->first();

            if ($this->exercises()->whereKey($exercise->getKey())->exists()) {
                return;
            }

            $lastOrder = DB::table('exercise_lesson')->where('lesson_id', $this->getKey())->max('order');

            $this->exercises()->attach($exercise->getKey(), [
                'order' => $lastOrder === null ? 0 : $lastOrder + 1,
            ]);
        });
    }

    /**
     * The earliest-ordered exercise the user has not completed, optionally only
     * after $afterOrder; null when there is none.
     */
    public function firstIncompleteExerciseId(User $user, ?int $afterOrder = null): ?int
    {
        $exerciseId = DB::table('exercise_lesson')
            ->where('lesson_id', $this->getKey())
            ->when($afterOrder !== null, fn ($q) => $q->where('order', '>', $afterOrder))
            ->whereNotIn('exercise_id', fn ($q) => $q
                ->select('exercise_id')
                ->from('user_exercise_completions')
                ->where('user_id', $user->getKey())
            )
            ->orderBy('order')
            ->value('exercise_id');

        return $exerciseId === null ? null : (int) $exerciseId;
    }

    /**
     * Where the user goes after answering the exercise at $afterOrder. They
     * move forward through the lesson's `order` first, so a correct answer
     * never sends them back to a question they skipped. Once nothing is left
     * ahead of them, the scan restarts from the top to pick up those gaps;
     * null means the lesson is finished.
     */
    public function nextIncompleteExerciseId(User $user, int $afterOrder): ?int
    {
        return $this->firstIncompleteExerciseId($user, $afterOrder)
            ?? $this->firstIncompleteExerciseId($user);
    }

    /**
     * The next lesson in the path, or null when this is the last. Lessons in a
     * path run in lesson-id order.
     */
    public function nextLessonId(LearningPath $learningPath): ?int
    {
        $lessonId = DB::table('learning_path_lesson')
            ->where('learning_path_id', $learningPath->getKey())
            ->where('lesson_id', '>', $this->getKey())
            ->orderBy('lesson_id')
            ->value('lesson_id');

        return $lessonId === null ? null : (int) $lessonId;
    }

    /**
     * The first exercise in a lesson's order. Takes an id so nextLessonId()'s
     * result can be used without loading the lesson.
     */
    public static function firstExerciseIdIn(int $lessonId): ?int
    {
        $exerciseId = DB::table('exercise_lesson')
            ->where('lesson_id', $lessonId)
            ->orderBy('order')
            ->value('exercise_id');

        return $exerciseId === null ? null : (int) $exerciseId;
    }

    public function learningPath()
    {
        return $this->belongsToMany(LearningPath::class, 'learning_path_lesson');
    }

    public function refreshCompletionStatus(): void
    {
        //
    }
}
