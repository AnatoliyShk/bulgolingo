<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\ExerciseType;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

#[Fillable(['name', 'email', 'password', 'is_admin', 'experience'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'experience' => 'integer',
        ];
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function learningPaths()
    {
        return $this->belongsToMany(LearningPath::class, 'learning_path_user')->using(LearningPathUser::class);
    }

    public function completedExercises()
    {
        return $this->belongsToMany(Exercise::class, 'user_exercise_completions')->using(UserExerciseCompletion::class)->withPivot('created_at', 'updated_at');
    }

    public function lexemas()
    {
        return $this->belongsToMany(Lexema::class, 'user_lexema', 'user_id', 'lexema_id')->using(UserLexema::class);
    }

    /**
     * Per learning path: each lesson in the order the path runs, flagged with
     * whether this user has finished it, plus the exercise types the path
     * covers. A lesson with no exercises is never complete, which is what makes
     * it the next thing to continue with.
     *
     * Counts are grouped by exercise type as well as by lesson, so a single
     * aggregate answers both questions — the per-lesson totals are the sum of
     * that lesson's type rows. Nothing is hydrated, so the cost stays flat no
     * matter how much the user has enrolled in.
     *
     * @param  array<int, int>  $pathIds
     * @return Collection<int, object> keyed by learning path id
     */
    public function lessonProgress(array $pathIds): Collection
    {
        return DB::table('learning_path_lesson as lpl')
            ->leftJoin('exercise_lesson as el', 'el.lesson_id', '=', 'lpl.lesson_id')
            ->leftJoin('exercises as e', 'e.id', '=', 'el.exercise_id')
            ->leftJoin('user_exercise_completions as uec', function ($join) {
                $join->on('uec.exercise_id', '=', 'el.exercise_id')
                    ->where('uec.user_id', $this->getKey());
            })
            ->whereIn('lpl.learning_path_id', $pathIds)
            ->groupBy('lpl.learning_path_id', 'lpl.lesson_id', 'e.decision_type')
            ->orderBy('lpl.lesson_id')
            ->select([
                'lpl.learning_path_id',
                'lpl.lesson_id',
                'e.decision_type',
                DB::raw('count(el.exercise_id) as total'),
                DB::raw('count(uec.exercise_id) as completed'),
            ])
            ->get()
            ->groupBy(fn ($row) => (int) $row->learning_path_id)
            ->map(fn ($rows) => (object) [
                'lessons' => $rows
                    ->groupBy(fn ($row) => (int) $row->lesson_id)
                    ->map(function ($lessonRows, $lessonId) {
                        $total = $lessonRows->sum(fn ($row) => (int) $row->total);
                        $completed = $lessonRows->sum(fn ($row) => (int) $row->completed);

                        return (object) [
                            'lesson_id' => (int) $lessonId,
                            'is_complete' => $total > 0 && $total === $completed,
                        ];
                    })
                    ->values(),
                'exercise_types' => $rows
                    ->pluck('decision_type')
                    ->filter()
                    ->unique()
                    ->map(fn ($type) => ExerciseType::tryFrom($type))
                    ->filter()
                    ->values(),
            ]);
    }
}
