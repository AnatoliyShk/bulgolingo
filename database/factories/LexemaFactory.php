<?php

namespace Database\Factories;

use App\Models\Lexema;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lexema>
 */
class LexemaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'word' => fake()->unique()->word(),
        ];
    }
}
