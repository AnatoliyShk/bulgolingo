<?php

namespace Database\Seeders;

use App\Models\Lesson;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lessons = [
            ['name' => 'Cyrillic Alphabet', 'description' => 'Learn to read and write the Bulgarian Cyrillic alphabet.'],
            ['name' => 'Greetings', 'description' => 'Basic greetings and introductions.'],
            ['name' => 'Numbers', 'description' => 'Counting from 1 to 100 in Bulgarian.'],
            ['name' => 'Family', 'description' => 'Vocabulary for family members and relationships.'],
            ['name' => 'Food and Drink', 'description' => 'Common food, drink, and restaurant vocabulary.'],
            ['name' => 'Days and Time', 'description' => 'Days of the week, months, and telling time.'],
            ['name' => 'Colors', 'description' => 'Names of common colors.'],
            ['name' => 'Directions', 'description' => 'Asking for and giving directions.'],
        ];

        foreach ($lessons as $lesson) {
            Lesson::create($lesson);
        }
    }
}
