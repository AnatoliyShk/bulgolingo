<?php

namespace Database\Factories;

use App\Models\Messenger;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Messenger>
 */
class MessengerFactory extends Factory
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
            'messenger_name' => fake()->randomElement(['Telegram', 'WhatsApp', 'Viber']),
            'messenger_user_id' => fake()->unique()->numerify('##########'),
        ];
    }
}
