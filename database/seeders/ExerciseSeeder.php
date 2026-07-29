<?php

namespace Database\Seeders;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\Lesson;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExerciseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(LessonSeeder::class);

        $exercisesByLesson = [
            'Cyrillic Alphabet' => [
                [
                    'name' => 'Match the letters',
                    'decision_type' => ExerciseType::MULTIPLE_CHOICE,
                    'clause' => [
                        'pairs' => [['A', 'А'], ['B', 'Б'], ['V', 'В'], ['G', 'Г']],
                        'explanation' => 'Match the Latin sound to its Cyrillic letter.',
                    ],
                ],
            ],
            'Greetings' => [
                [
                    'name' => 'True or false: Zdravey',
                    'decision_type' => ExerciseType::TRUE_FALSE,
                    'clause' => [
                        'sentence' => '"Здравей" means "Goodbye".',
                        'correct_option' => false,
                        'explanation' => '"Здравей" means "Hello". "Довиждане" means "Goodbye".',
                    ],
                ],
                [
                    'name' => 'Complete the greeting',
                    'decision_type' => ExerciseType::FILL_IN_THE_BLANK,
                    'clause' => [
                        'sentence' => '__, как си?',
                        'options' => ['Здравей', 'Довиждане', 'Благодаря'],
                        'correct_option' => 0,
                        'explanation' => '"Здравей, как си?" means "Hello, how are you?"',
                    ],
                ],
            ],
            'Numbers' => [
                [
                    'name' => 'Match the numbers',
                    'decision_type' => ExerciseType::MULTIPLE_CHOICE,
                    'clause' => [
                        'pairs' => [['One', 'Едно'], ['Two', 'Две'], ['Three', 'Три'], ['Four', 'Четири']],
                        'explanation' => 'Match the number to its Bulgarian translation.',
                    ],
                ],
                [
                    'name' => 'Simple addition',
                    'decision_type' => ExerciseType::FILL_IN_THE_BLANK,
                    'clause' => [
                        'sentence' => 'Три плюс едно е __.',
                        'options' => ['четири', 'пет', 'две'],
                        'correct_option' => 0,
                        'explanation' => 'Three plus one is four.',
                    ],
                ],
            ],
            'Family' => [
                [
                    'name' => 'Match family members',
                    'decision_type' => ExerciseType::MULTIPLE_CHOICE,
                    'clause' => [
                        'pairs' => [['Mother', 'Майка'], ['Father', 'Баща'], ['Sister', 'Сестра'], ['Brother', 'Брат']],
                        'explanation' => 'Match the family member to its Bulgarian translation.',
                    ],
                ],
                [
                    'name' => 'True or false: Bashta',
                    'decision_type' => ExerciseType::TRUE_FALSE,
                    'clause' => [
                        'sentence' => '"Баща" means "Mother".',
                        'correct_option' => false,
                        'explanation' => '"Баща" means "Father". "Майка" means "Mother".',
                    ],
                ],
            ],
            'Food and Drink' => [
                [
                    'name' => 'Match food and drink',
                    'decision_type' => ExerciseType::MULTIPLE_CHOICE,
                    'clause' => [
                        'pairs' => [['Bread', 'Хляб'], ['Water', 'Вода'], ['Cheese', 'Сирене'], ['Wine', 'Вино']],
                        'explanation' => 'Match the food or drink to its Bulgarian translation.',
                    ],
                ],
                [
                    'name' => 'Order a drink',
                    'decision_type' => ExerciseType::FILL_IN_THE_BLANK,
                    'clause' => [
                        'sentence' => 'Искам чаша __.',
                        'options' => ['вода', 'хляб', 'сирене'],
                        'correct_option' => 0,
                        'explanation' => '"Искам чаша вода" means "I want a glass of water."',
                    ],
                ],
            ],
            'Days and Time' => [
                [
                    'name' => 'True or false: Ponedelnik',
                    'decision_type' => ExerciseType::TRUE_FALSE,
                    'clause' => [
                        'sentence' => '"Понеделник" is the Bulgarian word for "Sunday".',
                        'correct_option' => false,
                        'explanation' => '"Понеделник" means "Monday". "Неделя" means "Sunday".',
                    ],
                ],
                [
                    'name' => 'What day is it?',
                    'decision_type' => ExerciseType::FILL_IN_THE_BLANK,
                    'clause' => [
                        'sentence' => 'Днес е __.',
                        'options' => ['понеделник', 'вторник', 'сряда'],
                        'correct_option' => 0,
                        'explanation' => '"Днес е понеделник" means "Today is Monday."',
                    ],
                ],
            ],
            'Colors' => [
                [
                    'name' => 'Match the colors',
                    'decision_type' => ExerciseType::MULTIPLE_CHOICE,
                    'clause' => [
                        'pairs' => [['Red', 'Червено'], ['Blue', 'Синьо'], ['Green', 'Зелено'], ['Yellow', 'Жълто']],
                        'explanation' => 'Match the color to its Bulgarian translation.',
                    ],
                ],
                [
                    'name' => 'Pick the color',
                    'decision_type' => ExerciseType::IMAGE_MATCHING,
                    'clause' => [
                        'options' => ['Червено', 'Синьо', 'Зелено', 'Жълто'],
                        'correct_option' => 0,
                        'explanation' => 'Select the Bulgarian word for the color shown.',
                    ],
                ],
            ],
            'Directions' => [
                [
                    'name' => 'True or false: Lyavo',
                    'decision_type' => ExerciseType::TRUE_FALSE,
                    'clause' => [
                        'sentence' => '"Ляво" means "Right".',
                        'correct_option' => false,
                        'explanation' => '"Ляво" means "Left". "Дясно" means "Right".',
                    ],
                ],
                [
                    'name' => 'Give directions',
                    'decision_type' => ExerciseType::FILL_IN_THE_BLANK,
                    'clause' => [
                        'sentence' => 'Завийте __ на светофара.',
                        'options' => ['наляво', 'надясно', 'направо'],
                        'correct_option' => 0,
                        'explanation' => '"Завийте наляво на светофара" means "Turn left at the traffic light."',
                    ],
                ],
            ],
        ];

        foreach ($exercisesByLesson as $lessonName => $exercises) {
            $lesson = Lesson::where('name', $lessonName)->first();

            if (! $lesson) {
                continue;
            }

            foreach ($exercises as $exercise) {
                Exercise::create([
                    'name' => $exercise['name'],
                    'lesson_id' => $lesson->id,
                    'decision_type' => $exercise['decision_type'],
                    'clause' => $exercise['clause'],
                ]);
            }
        }
    }
}
