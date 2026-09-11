<?php

namespace App\Models;

use App\Enums\LearningPathType;
use Database\Factories\LearningPathFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'language', 'type'])]
class LearningPath extends Model
{
    /** @use HasFactory<LearningPathFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => LearningPathType::class,
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

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'learning_path_user')->using(LearningPathUser::class);
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'learning_path_lesson')->using(LearningPathLesson::class)->withPivot('is_completed');
    }
}
