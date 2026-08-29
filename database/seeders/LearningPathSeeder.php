<?php

namespace Database\Seeders;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Lesson;
use Illuminate\Database\Seeder;

class LearningPathSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds one complete beginner path: 3 lessons, 7 exercises each.
     * Lessons and exercises are consumed in ascending id order by the
     * exercise player, so the insertion order below is the study order.
     */
    public function run(): void
    {
        $path = LearningPath::firstOrCreate(
            ['name' => 'Bulgarian Starter'],
            ['language' => 'BG'],
        );

        foreach ($this->lessons() as $lessonData) {
            $lesson = Lesson::firstOrCreate(
                ['name' => $lessonData['name']],
                ['description' => $lessonData['description']],
            );

            $path->lessons()->syncWithoutDetaching([$lesson->id]);

            foreach ($lessonData['exercises'] as $exercise) {
                $model = $lesson->exercises()->where('name', $exercise['name'])->first()
                    ?? Exercise::create([
                        'name' => $exercise['name'],
                        'decision_type' => $exercise['decision_type'],
                        'clause' => $exercise['clause'],
                    ]);

                $lesson->attachExerciseAtEnd($model);
            }
        }
    }

    /**
     * @return array<int, array{name: string, description: string, exercises: array<int, array>}>
     */
    private function lessons(): array
    {
        return [
            [
                'name' => 'Greetings and Politeness',
                'description' => 'Say hello, goodbye, and be polite in Bulgarian.',
                'exercises' => [
                    [
                        'name' => 'Match the greetings',
                        'decision_type' => ExerciseType::MULTIPLE_CHOICE,
                        'clause' => [
                            'pairs' => [
                                ['Hello', 'Здравей'],
                                ['Good morning', 'Добро утро'],
                                ['Good evening', 'Добър вечер'],
                                ['Goodbye', 'Довиждане'],
                                ['Good night', 'Лека нощ'],
                            ],
                            'explanation' => 'Match each English greeting to its Bulgarian equivalent.',
                        ],
                    ],
                    [
                        'name' => 'Greet the room',
                        'decision_type' => ExerciseType::FILL_IN_THE_BLANK,
                        'clause' => [
                            'sentence' => 'Добро __ на всички.',
                            'options' => ['утро', 'вечер', 'нощ'],
                            'correct_option' => 0,
                            'explanation' => '"Добро утро на всички." means "Good morning, everyone." Утро is the word for morning.',
                        ],
                    ],
                    [
                        'name' => 'True or false: Blagodarya',
                        'decision_type' => ExerciseType::TRUE_FALSE,
                        'clause' => [
                            'sentence' => '"Благодаря" is how you say "thank you" in Bulgarian.',
                            'correct_option' => true,
                            'explanation' => 'Correct. "Благодаря" means "thank you". A shorter, more casual form is "мерси".',
                        ],
                    ],
                    [
                        'name' => 'Ask politely',
                        'decision_type' => ExerciseType::FILL_IN_THE_BLANK,
                        'clause' => [
                            'sentence' => 'Кажи __ преди да поискаш нещо.',
                            'options' => ['моля', 'благодаря', 'довиждане'],
                            'correct_option' => 0,
                            'explanation' => '"Кажи моля преди да поискаш нещо." means "Say please before you ask for something."',
                        ],
                    ],
                    [
                        'name' => 'Match the polite phrases',
                        'decision_type' => ExerciseType::MULTIPLE_CHOICE,
                        'clause' => [
                            'pairs' => [
                                ['Please', 'Моля'],
                                ['Thank you', 'Благодаря'],
                                ['Excuse me', 'Извинете'],
                                ['You are welcome', 'Няма защо'],
                                ['Sorry', 'Съжалявам'],
                            ],
                            'explanation' => 'Match each polite English phrase to its Bulgarian equivalent.',
                        ],
                    ],
                    [
                        'name' => 'Ask how someone is',
                        'decision_type' => ExerciseType::FILL_IN_THE_BLANK,
                        'clause' => [
                            'sentence' => 'Здравей, как __?',
                            'options' => ['си', 'съм', 'сме'],
                            'correct_option' => 0,
                            'explanation' => '"Здравей, как си?" means "Hello, how are you?" Си is the second person singular of "съм".',
                        ],
                    ],
                    [
                        'name' => 'True or false: Dovizhdane',
                        'decision_type' => ExerciseType::TRUE_FALSE,
                        'clause' => [
                            'sentence' => '"Довиждане" is what you say when you arrive somewhere.',
                            'correct_option' => false,
                            'explanation' => '"Довиждане" means "goodbye", so it is what you say on the way out. On the way in say "Здравей".',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'People and Places',
                'description' => 'Everyday nouns for the people around you and the places you go.',
                'exercises' => [
                    [
                        'name' => 'Match the people',
                        'decision_type' => ExerciseType::MULTIPLE_CHOICE,
                        'clause' => [
                            'pairs' => [
                                ['Man', 'Мъж'],
                                ['Woman', 'Жена'],
                                ['Boy', 'Момче'],
                                ['Girl', 'Момиче'],
                                ['Child', 'Дете'],
                            ],
                            'explanation' => 'Match each English word for a person to its Bulgarian equivalent.',
                        ],
                    ],
                    [
                        'name' => 'Introduce yourself',
                        'decision_type' => ExerciseType::FILL_IN_THE_BLANK,
                        'clause' => [
                            'sentence' => 'Аз съм __ от София.',
                            'options' => ['студент', 'град', 'улица'],
                            'correct_option' => 0,
                            'explanation' => '"Аз съм студент от София." means "I am a student from Sofia."',
                        ],
                    ],
                    [
                        'name' => 'True or false: Grad',
                        'decision_type' => ExerciseType::TRUE_FALSE,
                        'clause' => [
                            'sentence' => '"Град" means "street" in Bulgarian.',
                            'correct_option' => false,
                            'explanation' => '"Град" means "city". The word for "street" is "улица".',
                        ],
                    ],
                    [
                        'name' => 'Find the shop',
                        'decision_type' => ExerciseType::FILL_IN_THE_BLANK,
                        'clause' => [
                            'sentence' => 'Магазинът е на голямата __ до парка.',
                            'options' => ['улица', 'къща', 'стая'],
                            'correct_option' => 0,
                            'explanation' => '"Магазинът е на голямата улица до парка." means "The shop is on the big street next to the park."',
                        ],
                    ],
                    [
                        'name' => 'Match the places',
                        'decision_type' => ExerciseType::MULTIPLE_CHOICE,
                        'clause' => [
                            'pairs' => [
                                ['City', 'Град'],
                                ['Village', 'Село'],
                                ['House', 'Къща'],
                                ['Shop', 'Магазин'],
                                ['Street', 'Улица'],
                            ],
                            'explanation' => 'Match each English place to its Bulgarian equivalent.',
                        ],
                    ],
                    [
                        'name' => 'True or false: Momiche',
                        'decision_type' => ExerciseType::TRUE_FALSE,
                        'clause' => [
                            'sentence' => '"Момиче" means "boy" in Bulgarian.',
                            'correct_option' => false,
                            'explanation' => '"Момиче" means "girl". The word for "boy" is "момче", so watch the ending.',
                        ],
                    ],
                    [
                        'name' => 'Where the children play',
                        'decision_type' => ExerciseType::FILL_IN_THE_BLANK,
                        'clause' => [
                            'sentence' => 'Децата играят пред голямата __.',
                            'options' => ['къща', 'вода', 'книга'],
                            'correct_option' => 0,
                            'explanation' => '"Децата играят пред голямата къща." means "The children play in front of the big house."',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Simple Sentences',
                'description' => 'Put the verb "to be" to work and build your first full sentences.',
                'exercises' => [
                    [
                        'name' => 'True or false: Az sum',
                        'decision_type' => ExerciseType::TRUE_FALSE,
                        'clause' => [
                            'sentence' => '"Аз съм" means "I am".',
                            'correct_option' => true,
                            'explanation' => 'Correct. "Аз" means "I" and "съм" is the first person singular of the verb "to be".',
                        ],
                    ],
                    [
                        'name' => 'You are from Bulgaria',
                        'decision_type' => ExerciseType::FILL_IN_THE_BLANK,
                        'clause' => [
                            'sentence' => 'Ти __ от България.',
                            'options' => ['си', 'съм', 'сме'],
                            'correct_option' => 0,
                            'explanation' => '"Ти си от България." means "You are from Bulgaria." Си is the second person singular of "съм".',
                        ],
                    ],
                    [
                        'name' => 'Match the pronouns',
                        'decision_type' => ExerciseType::MULTIPLE_CHOICE,
                        'clause' => [
                            'pairs' => [
                                ['I am', 'Аз съм'],
                                ['You are', 'Ти си'],
                                ['He is', 'Той е'],
                                ['We are', 'Ние сме'],
                                ['They are', 'Те са'],
                            ],
                            'explanation' => 'Match each English pronoun with "to be" to its Bulgarian equivalent.',
                        ],
                    ],
                    [
                        'name' => 'We study Bulgarian',
                        'decision_type' => ExerciseType::FILL_IN_THE_BLANK,
                        'clause' => [
                            'sentence' => 'Ние __ български всеки ден.',
                            'options' => ['учим', 'уча', 'учат'],
                            'correct_option' => 0,
                            'explanation' => '"Ние учим български всеки ден." means "We study Bulgarian every day."',
                        ],
                    ],
                    [
                        'name' => 'True or false: Ne razbiram',
                        'decision_type' => ExerciseType::TRUE_FALSE,
                        'clause' => [
                            'sentence' => '"Не разбирам" means "I understand".',
                            'correct_option' => false,
                            'explanation' => '"Не разбирам" means "I do not understand". Drop the "не" and "разбирам" means "I understand".',
                        ],
                    ],
                    [
                        'name' => 'He is a teacher',
                        'decision_type' => ExerciseType::FILL_IN_THE_BLANK,
                        'clause' => [
                            'sentence' => 'Той __ учител в училището.',
                            'options' => ['е', 'са', 'сте'],
                            'correct_option' => 0,
                            'explanation' => '"Той е учител в училището." means "He is a teacher at the school." Е is the third person singular of "съм".',
                        ],
                    ],
                    [
                        'name' => 'Match the short sentences',
                        'decision_type' => ExerciseType::MULTIPLE_CHOICE,
                        'clause' => [
                            'pairs' => [
                                ['I am a student', 'Аз съм студент'],
                                ['You are here', 'Ти си тук'],
                                ['She is at home', 'Тя е вкъщи'],
                                ['We are friends', 'Ние сме приятели'],
                                ['They are Bulgarian', 'Те са българи'],
                            ],
                            'explanation' => 'Match each English sentence to its Bulgarian equivalent.',
                        ],
                    ],
                ],
            ],
        ];
    }
}
