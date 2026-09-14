<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Models\LearningPath;
use Database\Seeders\Concerns\SeedsLearningPaths;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Tests\TestCase;
use ValueError;

class SeedsLearningPathsTest extends TestCase
{
    use RefreshDatabase;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/seeds-learning-paths-'.uniqid();
        File::ensureDirectoryExists($this->directory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    private function pathSeeder(): Seeder
    {
        return new class extends Seeder
        {
            use SeedsLearningPaths;

            public string $directory;

            public function run(): void
            {
                $this->seedPathsFrom($this->directory);
            }
        };
    }

    private function writePath(string $file, array $exercises, string $name = 'Path'): void
    {
        File::put($this->directory.'/'.$file, json_encode([
            'name' => $name,
            'level' => 'A1',
            'lessons' => [['name' => 'Lesson', 'description' => 'A lesson.', 'exercises' => $exercises]],
        ]));
    }

    private function seedDirectory(): void
    {
        $seeder = $this->pathSeeder();
        $seeder->directory = $this->directory;
        $seeder->run();
    }

    private static function trueFalse(string $name): array
    {
        return ['true_false' => ['name' => $name, 'sentence' => 'Да.', 'correct_option' => true, 'explanation' => 'Yes.']];
    }

    public function test_the_type_key_becomes_the_decision_type_and_the_rest_the_clause(): void
    {
        $this->writePath('01-path.json', [self::trueFalse('Yes')]);

        $this->seedDirectory();

        $exercise = LearningPath::firstOrFail()->lessons()->firstOrFail()->exercises()->firstOrFail();

        $this->assertSame('Yes', $exercise->name);
        $this->assertSame(ExerciseType::TRUE_FALSE, $exercise->decision_type);
        $this->assertEquals(['sentence' => 'Да.', 'correct_option' => true, 'explanation' => 'Yes.'], $exercise->clause);
    }

    public function test_files_are_seeded_in_filename_order(): void
    {
        $this->writePath('02-second.json', [self::trueFalse('Second')], 'Second');
        $this->writePath('01-first.json', [self::trueFalse('First')], 'First');

        $this->seedDirectory();

        $this->assertSame(['First', 'Second'], LearningPath::orderBy('id')->pluck('name')->all());
    }

    public function test_an_entry_with_two_type_keys_is_refused(): void
    {
        $this->writePath('01-path.json', [self::trueFalse('Yes') + ['multiple_choice' => []]]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exactly one type key');

        $this->seedDirectory();
    }

    public function test_an_unknown_type_is_refused(): void
    {
        $this->writePath('01-path.json', [['essay' => ['name' => 'Write', 'explanation' => 'No.']]]);

        $this->expectException(ValueError::class);

        $this->seedDirectory();
    }

    public function test_an_empty_directory_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->seedDirectory();
    }
}
