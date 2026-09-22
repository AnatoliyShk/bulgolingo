<?php

namespace Database\Factories;

use App\Models\DesiredTopic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DesiredTopic>
 */
class DesiredTopicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'topic' => fake()->unique()->words(3, true),
        ];
    }
}
