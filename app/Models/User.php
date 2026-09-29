<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RoleName;
use App\Enums\UserType;
use App\Models\Concerns\HasUuidV7;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role_id', 'experience', 'type_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements OAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuidV7, Notifiable;

    /**
     * Mirrors the database default so a freshly created user reads 0, not null.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'streak_counter' => 0,
    ];

    /**
     * A user created without a role or type is a regular student, so
     * registration, factories and seeders only name one when it is something
     * more.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->role_id ??= Role::named(RoleName::Student)->id;
            $user->type_id ??= Type::named(UserType::Regular)->id;
        });
    }

    /**
     * Every user as {id, name}, alphabetical, for the admin forms' user
     * pickers.
     *
     * @return Collection<int, User>
     */
    public static function pickerOptions(): Collection
    {
        return static::orderBy('name')->get(['id', 'name']);
    }

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
            'experience' => 'integer',
            'streak_counter' => 'integer',
            'latest_exercise_at' => 'datetime',
        ];
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

    /**
     * Points the avatar at $path, or clears it with null, and deletes the
     * object it pointed at before. The old object is removed rather than left
     * behind, because nothing else ever refers to it once the column moves
     * on: keeping it would grow the bucket by one orphan per change with no
     * way to find them again. It goes only after the column is saved, so a
     * failed save never leaves the user pointing at a deleted picture.
     */
    public function replaceAvatar(?string $path): void
    {
        $previous = $this->avatar_path;

        $this->forceFill(['avatar_path' => $path])->save();

        if ($previous) {
            Images::deleteFile($previous);
        }
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function hasRole(RoleName $name): bool
    {
        return $this->role?->name === $name;
    }

    public function hasType(UserType $name): bool
    {
        return $this->type?->name === $name;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(RoleName::Admin);
    }

    public function isAdminVisitor(): bool
    {
        return $this->hasRole(RoleName::AdminVisitor);
    }

    /**
     * Whether this user may enter the admin panel at all, as a full admin or
     * as a read-only visitor. Gates the `admin` route middleware; the visitor's
     * further restrictions (no user records, no writes) are enforced separately
     * by RestrictAdminVisitor.
     */
    public function canAccessAdminPanel(): bool
    {
        return $this->isAdmin() || $this->isAdminVisitor();
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

    public function desiredTopics(): HasMany
    {
        return $this->hasMany(DesiredTopic::class);
    }
}
