<?php

namespace Database\Factories;

use App\Models\LearnedWords;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearnedWords>
 */
class LearnedWordsFactory extends Factory
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
