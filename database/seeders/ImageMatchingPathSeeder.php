<?php

namespace Database\Seeders;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\Images;
use App\Models\LearningPath;
use App\Models\Lesson;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ImageMatchingPathSeeder extends Seeder
{
    /**
     * Seed pictures are read from this project-root folder, which is outside
     * version control, and copied to the bucket folder the admin panel already
     * uploads exercise images to.
     */
    private const SOURCE_DIR = 'images-for-exercises';

    private const TARGET_DIR = 'exercise-images';

    /**
     * Seeds one picture path: a single lesson of 3 image matching exercises,
     * each owning one picture. Exercises are consumed in ascending id order by
     * the player, so the order in exercises() is the study order. Path, lesson,
     * exercise and image rows are all keyed by a natural key, so a second run
     * fills gaps instead of duplicating the path.
     */
    public function run(): void
    {
        $path = LearningPath::firstOrCreate(
            ['name' => 'Bulgarian in Pictures'],
            ['language' => 'BG'],
        );

        $lesson = Lesson::firstOrCreate(
            ['name' => 'Describe the Picture'],
            ['description' => 'Look at each picture and pick the Bulgarian sentence that describes it.'],
        );

        $path->lessons()->syncWithoutDetaching([$lesson->id]);

        foreach ($this->exercises() as $data) {
            $exercise = Exercise::firstOrCreate(
                ['name' => $data['name'], 'lesson_id' => $lesson->id],
                ['decision_type' => ExerciseType::IMAGE_MATCHING, 'clause' => $data['clause']],
            );

            $image = $this->storeImage($data['image']);

            if ($image) {
                $exercise->images()->syncWithoutDetaching([$image->id]);
            }
        }
    }

    /**
     * Copies one seed picture into the bb_images bucket and registers it,
     * reusing the row when the object is already there. The player reaches the
     * file through Images::getUrlAttribute(), so the stored path has to stay
     * relative to the disk. A picture missing from the source folder is
     * reported and skipped rather than aborting: the folder is gitignored, so a
     * fresh checkout may not have it and the rest of the path still seeds.
     */
    private function storeImage(string $filename): ?Images
    {
        $source = base_path(self::SOURCE_DIR.'/'.$filename);

        if (! is_file($source)) {
            $this->command?->warn("Skipping image {$filename}: not found in ".self::SOURCE_DIR.'/');

            return null;
        }

        $target = self::TARGET_DIR.'/'.$filename;

        if (! Storage::disk(Images::DISK)->exists($target)) {
            Storage::disk(Images::DISK)->put($target, file_get_contents($source));
        }

        return Images::firstOrCreate(['filepath' => $target]);
    }

    /**
     * @return array<int, array{name: string, image: string, clause: array}>
     */
    private function exercises(): array
    {
        return [
            [
                'name' => 'The green ogre',
                'image' => 'shrek.png',
                'clause' => [
                    'options' => [
                        'Розовото прасе стои на тревата.',
                        'Зеленият огър се усмихва широко.',
                        'Балерината танцува на сцената.',
                        'Момичето пие кафе в кафенето.',
                    ],
                    'correct_option' => 1,
                    'explanation' => '"Зеленият огър се усмихва широко." means "The green ogre smiles widely." Зелен is "green" and усмихвам се is "to smile".',
                ],
            ],
            [
                'name' => 'The pink pig',
                'image' => 'minecraft-pig.png',
                'clause' => [
                    'options' => [
                        'Розовото прасе стои на зелената трева.',
                        'Кучето спи пред голямата къща.',
                        'Огърът яде в блатото.',
                        'Чашата кафе е на масата.',
                    ],
                    'correct_option' => 0,
                    'explanation' => '"Розовото прасе стои на зелената трева." means "The pink pig is standing on the green grass." Прасе is "pig" and трева is "grass".',
                ],
            ],
            [
                'name' => 'The dancing coffee cup',
                'image' => 'balerina-capuchino.png',
                'clause' => [
                    'options' => [
                        'Мъжът пие чаша вода.',
                        'Прасето тича към гората.',
                        'Балерината с чаша кафе танцува.',
                        'Детето рисува голяма къща.',
                    ],
                    'correct_option' => 2,
                    'explanation' => '"Балерината с чаша кафе танцува." means "The ballerina with a cup of coffee is dancing." Кафе is "coffee" and танцувам is "to dance".',
                ],
            ],
        ];
    }
}
