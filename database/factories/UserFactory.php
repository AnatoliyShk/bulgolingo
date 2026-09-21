<?php

namespace Database\Factories;

use App\Enums\RoleName;
use App\Enums\UserType;
use App\Models\Role;
use App\Models\Type;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'type_id' => Type::named(UserType::Regular)->id,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::named(RoleName::Admin)->id,
        ]);
    }

    public function adminVisitor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::named(RoleName::AdminVisitor)->id,
        ]);
    }

    public function playwright(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_id' => Type::named(UserType::Playwright)->id,
        ]);
    }

    public function filler(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_id' => Type::named(UserType::Filler)->id,
        ]);
    }

    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_id' => Type::named(UserType::Premium)->id,
        ]);
    }

    public function mcpClient(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_id' => Type::named(UserType::McpClient)->id,
        ]);
    }
}
