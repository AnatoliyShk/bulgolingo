<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\ExerciseType;
use App\Enums\UserType;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'password', 'is_admin', 'experience', 'type'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Mirrors the database default so a freshly created user reads 0, not null.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'streak_counter' => 0,
    ];

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
            'streak_counter' => 'integer',
            'latest_exercise_at' => 'datetime',
            'type' => UserType::class,
        ];
    }

    /**
     * Stamps practice and advances the day streak: +1 after a yesterday
     * completion, reset to 1 after a gap, unchanged (but at least 1) on a repeat
     * the same day. A single conditional UPDATE so concurrent answers cannot both
     * advance it, with day bounds from the app clock; refresh to read the result.
     */
    public function recordPractice(): void
    {
        $now = now();

        DB::update(
            'update '.$this->getTable().' set
                streak_counter = case
                    when latest_exercise_at >= ? then greatest(streak_counter, 1)
                    when latest_exercise_at >= ? then streak_counter + 1
                    else 1
                end,
                latest_exercise_at = ?
             where id = ?',
            [
                $now->copy()->startOfDay(),
                $now->copy()->subDay()->startOfDay(),
                $now,
                $this->getKey(),
            ]
        );
    }

    /**
     * Signed avatar URL, or null when unset. A method, not an appended attribute,
     * so the shared auth.user prop does not sign a URL on every request.
     */
    public function avatarUrl(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        return Storage::disk(Images::DISK)->temporaryUrl($this->avatar_path, now()->addHour());
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function learningPaths()
    {
        return $this->belongsToMany(LearningPath::class, 'learning_path_user')->using(LearningPathUser::class)->withTimestamps();
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
     * Per path: its lessons in order, each flagged complete for this user, and
     * the exercise types it covers. A lesson with no exercises is never complete.
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

    /**
     * Adds this user's progress to each path: lessons finished, lesson to
     * continue, exercise types, and whether it is finished. $paths must carry
     * withCount('lessons').
     *
     * @param  Collection<int, LearningPath>  $paths
     * @return Collection<int, LearningPath>
     */
    public function decoratePathsWithProgress(Collection $paths): Collection
    {
        if ($paths->isEmpty()) {
            return $paths;
        }

        $progress = $this->lessonProgress($paths->modelKeys());

        return $paths->each(function (LearningPath $path) use ($progress) {
            $row = $progress->get($path->id);
            $lessons = $row?->lessons ?? collect();

            $path->completed_lessons_count = $lessons->where('is_complete', true)->count();
            $path->continue_lesson_id = $lessons->firstWhere('is_complete', false)?->lesson_id;
            $path->exercise_types = ($row?->exercise_types ?? collect())
                ->map(fn (ExerciseType $type) => $type->getDescription())
                ->all();
            $path->is_finished = $lessons->isNotEmpty() && $lessons->every(fn ($lesson) => $lesson->is_complete);
        });
    }

    /**
     * Enrolled paths with progress, most recently enrolled first. Rows without an
     * enrollment timestamp sort last, as Postgres puts nulls first on DESC.
     *
     * @return Collection<int, LearningPath>
     */
    public function enrolledPathsWithProgress(): Collection
    {
        return $this->decoratePathsWithProgress(
            $this->learningPaths()
                ->withCount('lessons')
                ->orderByRaw("coalesce(learning_path_user.created_at, '1970-01-01') desc")
                ->get()
        );
    }
}
