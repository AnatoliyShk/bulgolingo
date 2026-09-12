<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Enums\LearningPathType;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\Lexema;
use App\Models\User;
use App\Services\GradeLexemeReview;
use App\Support\LoadTest\RunManifest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The local disk is faked so no manifest exists, which is the situation
 * --orphans is for: data a run left behind with nothing on disk to say which
 * id ranges it owned.
 */
class LoadTestTeardownOrphansTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    /**
     * Builds one path, lesson and exercise with a lexema, named with $prefix,
     * and gives $user a completion, an enrolment and a graded review on it, so
     * every table the sweep walks holds a row for this tree.
     *
     * @return array{path: LearningPath, lesson: Lesson, exercise: Exercise, lexema: Lexema}
     */
    private function tree(string $prefix, User $user): array
    {
        $path = LearningPath::create(['name' => $prefix.'Path', 'language' => 'bg', 'type' => LearningPathType::Regular->value]);
        $lesson = Lesson::create(['name' => $prefix.'Lesson', 'description' => 'Test']);

        $exercise = Exercise::create([
            'name' => $prefix.'Exercise',
            'decision_type' => ExerciseType::FILL_IN_THE_BLANK->value,
            'clause' => [
                'sentence' => 'The ___ is an animal.',
                'options' => ['Куче', 'Котка'],
                'correct_option' => 0,
                'explanation' => 'Test.',
            ],
        ]);

        $path->lessons()->attach($lesson, ['is_completed' => false]);
        $lesson->attachExerciseAtEnd($exercise);

        $lexema = Lexema::create(['word' => $prefix.'куче', 'exercise_id' => $exercise->id]);

        $user->learningPaths()->attach($path);
        DB::table('user_exercise_completions')->insert(['user_id' => $user->id, 'exercise_id' => $exercise->id, 'created_at' => now()]);
        app(GradeLexemeReview::class)->grade($user, $lexema, isCorrect: true, hintUsed: false, responseMs: 1000);

        return compact('path', 'lesson', 'exercise', 'lexema');
    }

    /**
     * Two filler users with --batch=1 make each table take more than one
     * statement, so the loop is shown to keep going until nothing matches
     * rather than stopping after its first batch.
     */
    public function test_orphans_removes_every_marked_row_and_leaves_real_data_alone(): void
    {
        $realUser = User::factory()->create();
        $real = $this->tree('', $realUser);

        $fillers = User::factory()->filler()->count(2)->create();
        $generated = $this->tree(RunManifest::NAME_PREFIX.' ', $fillers[0]);
        $this->tree(RunManifest::NAME_PREFIX.' second ', $fillers[1]);

        $this->artisan('loadtest:teardown', ['--orphans' => true, '--force' => true, '--batch' => 1])
            ->expectsOutputToContain('No load-test rows remain.')
            ->assertSuccessful();

        foreach ($fillers as $filler) {
            $this->assertDatabaseMissing('users', ['id' => $filler->id]);

            foreach (['review_logs', 'user_lexema', 'user_exercise_completions', 'learning_path_user'] as $table) {
                $this->assertDatabaseMissing($table, ['user_id' => $filler->id]);
            }
        }

        $this->assertDatabaseMissing('lexemas', ['id' => $generated['lexema']->id]);
        $this->assertDatabaseMissing('exercise_lesson', ['exercise_id' => $generated['exercise']->id]);
        $this->assertDatabaseMissing('learning_path_lesson', ['lesson_id' => $generated['lesson']->id]);
        $this->assertSame(0, Exercise::where('name', 'like', RunManifest::NAME_PREFIX.'%')->count());
        $this->assertSame(0, Lesson::where('name', 'like', RunManifest::NAME_PREFIX.'%')->count());
        $this->assertSame(0, LearningPath::where('name', 'like', RunManifest::NAME_PREFIX.'%')->count());

        $this->assertDatabaseHas('users', ['id' => $realUser->id]);
        $this->assertDatabaseHas('exercises', ['id' => $real['exercise']->id]);
        $this->assertDatabaseHas('lessons', ['id' => $real['lesson']->id]);
        $this->assertDatabaseHas('learning_paths', ['id' => $real['path']->id]);
        $this->assertDatabaseHas('lexemas', ['id' => $real['lexema']->id]);
        $this->assertDatabaseHas('exercise_lesson', ['exercise_id' => $real['exercise']->id]);
        $this->assertDatabaseHas('learning_path_lesson', ['lesson_id' => $real['lesson']->id]);

        foreach (['review_logs', 'user_lexema', 'user_exercise_completions', 'learning_path_user'] as $table) {
            $this->assertDatabaseHas($table, ['user_id' => $realUser->id]);
        }
    }

    /**
     * Covers the manifest path through the batch helper it now shares with the
     * orphan sweep: the recorded user block goes, its children with it, the
     * manifest is forgotten, and a filler user outside the block is untouched.
     */
    public function test_a_manifested_run_is_removed_by_its_recorded_range(): void
    {
        $inRun = User::factory()->filler()->create();
        $outside = User::factory()->filler()->create();
        DB::table('learning_path_user')->insert([
            'learning_path_id' => LearningPath::create(['name' => 'Real', 'language' => 'bg', 'type' => LearningPathType::Regular->value])->id,
            'user_id' => $inRun->id,
        ]);

        $manifest = RunManifest::start('small');
        $manifest->recordBlock('users', $inRun->id, 1);
        $manifest->save();

        $this->artisan('loadtest:teardown', ['run' => $manifest->id, '--force' => true])
            ->expectsOutputToContain('users typed filler: 1')
            ->assertSuccessful();

        $this->assertDatabaseMissing('users', ['id' => $inRun->id]);
        $this->assertDatabaseMissing('learning_path_user', ['user_id' => $inRun->id]);
        $this->assertDatabaseHas('users', ['id' => $outside->id]);
        $this->assertSame([], RunManifest::all());
    }

    public function test_without_orphans_the_marked_rows_are_only_reported(): void
    {
        $filler = User::factory()->filler()->create();
        $this->tree(RunManifest::NAME_PREFIX.' ', $filler);

        $this->artisan('loadtest:teardown')
            ->expectsOutputToContain('users typed filler: 1')
            ->expectsOutputToContain('loadtest:teardown --orphans')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['id' => $filler->id]);
        $this->assertSame(1, Exercise::where('name', 'like', RunManifest::NAME_PREFIX.'%')->count());
    }

    public function test_orphans_asks_before_deleting_and_does_nothing_when_declined(): void
    {
        $filler = User::factory()->filler()->create();

        $this->artisan('loadtest:teardown', ['--orphans' => true])
            ->expectsConfirmation('Delete every unmanifested load-test row from this database?', 'no')
            ->assertFailed();

        $this->assertDatabaseHas('users', ['id' => $filler->id]);
    }
}
