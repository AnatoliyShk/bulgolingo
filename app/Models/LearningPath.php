<?php

namespace App\Models;

use App\Enums\LanguageLevel;
use App\Enums\LearningPathType;
use App\Models\Concerns\HasUuidV7;
use Database\Factories\LearningPathFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

#[Fillable(['name', 'language', 'type', 'level'])]
class LearningPath extends Model
{
    /** @use HasFactory<LearningPathFactory> */
    use HasFactory, HasUuidV7;

    protected function casts(): array
    {
        return [
            'type' => LearningPathType::class,
            'level' => LanguageLevel::class,
        ];
    }

    /**
     * Keeps language_id in step with the free-text `language` ('BG') while
     * both exist: it is looked up, case-insensitively, against
     * languages.code, and is null when the text names no language. The text
     * column goes in a later migration, and the relation to Language is added
     * then, since it would collide with the `language` attribute until it is
     * gone.
     */
    protected static function booted(): void
    {
        static::saving(function (LearningPath $path) {
            if ($path->isDirty('language') || ! $path->exists) {
                $path->language_id = Language::query()->where('code', mb_strtolower((string) $path->language))->value('id');
            }
        });
    }

    /**
     * Limits paths to the types the viewer may see (null for a guest). Every
     * student-facing listing must go through this.
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        return $query->whereIn('type', LearningPathType::visibleTo($user));
    }

    /**
     * The visibleTo rule for a single loaded path, for routes that take an id.
     */
    public function isVisibleTo(?User $user): bool
    {
        return in_array($this->type->value, LearningPathType::visibleTo($user), true);
    }

    /**
     * The visibleTo rule for content reached through $paths, a query of the
     * paths a lesson or exercise belongs to: one path the user can see is
     * enough, and content in no path at all is hidden by none.
     */
    public static function admitsTo(Builder $paths, ?User $user): bool
    {
        return (clone $paths)->visibleTo($user)->exists() || $paths->doesntExist();
    }

    /**
     * Every learning path's exercise count, keyed by id. A path with no
     * lessons or no exercises is simply absent rather than zero.
     *
     * @return Collection<int, int>
     */
    public static function exerciseCountsById(): Collection
    {
        return DB::table('learning_path_lesson as lpl')
            ->join('exercise_lesson as el', 'el.lesson_id', '=', 'lpl.lesson_id')
            ->select('lpl.learning_path_id', DB::raw('count(distinct el.exercise_id) as exercise_count'))
            ->groupBy('lpl.learning_path_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->learning_path_id => (int) $row->exercise_count]);
    }

    /**
     * The distinct exercise types (raw decision_type values) each of $pathIds
     * covers, keyed by path id. A path with no exercises is absent.
     *
     * @param  array<int, int>  $pathIds
     * @return Collection<int, Collection<int, string>>
     */
    public static function exerciseTypesById(array $pathIds): Collection
    {
        return DB::table('learning_path_lesson as lpl')
            ->join('exercise_lesson as el', 'el.lesson_id', '=', 'lpl.lesson_id')
            ->join('exercises as e', 'e.id', '=', 'el.exercise_id')
            ->whereIn('lpl.learning_path_id', $pathIds)
            ->select('lpl.learning_path_id', 'e.decision_type')
            ->distinct()
            ->get()
            ->groupBy(fn ($row) => (int) $row->learning_path_id)
            ->map(fn ($rows) => $rows->pluck('decision_type')->values());
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'learning_path_user')->using(LearningPathUser::class);
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'learning_path_lesson')->using(LearningPathLesson::class);
    }
}
