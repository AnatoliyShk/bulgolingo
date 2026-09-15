<?php

namespace App\Models;

use App\Enums\RoleName;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The rows are created by the migration that adds the table, one per
 * RoleName case, so code can look a role up by name without seeding first.
 */
#[Fillable(['name'])]
#[Appends(['label'])]
class Role extends Model
{
    protected function casts(): array
    {
        return [
            'name' => RoleName::class,
        ];
    }

    public static function named(RoleName $name): self
    {
        return static::where('name', $name)->firstOrFail();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    protected function label(): Attribute
    {
        return Attribute::get(fn () => $this->name->label());
    }
}
