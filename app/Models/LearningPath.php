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
