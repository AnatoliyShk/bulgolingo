<?php

namespace Tests\Feature\Mcp;

use App\Enums\ExerciseType;
use App\Mcp\Resources\LessonResource;
use App\Mcp\Servers\ContentServer;
use App\Models\Exercise;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonResourceTest extends TestCase
{
    use RefreshDatabase;

    private function exercise(string $name): Exercise
    {
        return Exercise::create([
            'name' => $name,
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => ['sentence' => 'Здравей means hello.', 'correct_option' => true, 'explanation' => 'E.'],
        ]);
    }

    public function test_it_reads_a_lesson_by_uuid(): void
    {
        $lesson = Lesson::create(['name' => 'Greetings', 'description' => 'Say hello']);

        ContentServer::resource(LessonResource::class, ['uuid' => $lesson->uuid])
            ->assertOk()
            ->assertSee(['Greetings', 'Say hello']);
    }

    /**
     * Asserted as one encoded fragment rather than as two names, since the
     * point is the pivot order the lesson hands them back in.
     */
    public function test_it_lists_the_lessons_exercises_in_order(): void
    {
        $lesson = Lesson::create(['name' => 'Greetings', 'description' => 'D']);
        $first = $this->exercise('First');
        $second = $this->exercise('Second');
        $lesson->attachExerciseAtEnd($first);
        $lesson->attachExerciseAtEnd($second);

        ContentServer::resource(LessonResource::class, ['uuid' => $lesson->uuid])
            ->assertOk()
            ->assertSee(json_encode([
                ['uuid' => $first->uuid, 'name' => 'First', 'type' => ExerciseType::TRUE_FALSE->value],
                ['uuid' => $second->uuid, 'name' => 'Second', 'type' => ExerciseType::TRUE_FALSE->value],
            ]));
    }

    /**
     * The clause holds the question's correct answer, so it must stay out for
     * the reason ListExercisesTool keeps it out: nothing authenticates a reader.
     */
    public function test_it_never_returns_an_exercise_clause(): void
    {
        $lesson = Lesson::create(['name' => 'Greetings', 'description' => 'D']);
        $lesson->attachExerciseAtEnd($this->exercise('First'));

        ContentServer::resource(LessonResource::class, ['uuid' => $lesson->uuid])
            ->assertOk()
            ->assertDontSee(['correct_option', 'clause']);
    }

    /**
     * Also stands in for a registration check: the server resolves the uri
     * against its registered templates before the handler runs, so an
     * unregistered resource would fail here with "not found" too — and the
     * reads above would not have reached the handler at all. A templated
     * resource is listed under resources/templates/list, which the resources()
     * test helper does not read.
     */
    public function test_an_unknown_uuid_is_an_error(): void
    {
        ContentServer::resource(LessonResource::class, ['uuid' => '01930000-0000-7000-8000-000000000000'])
            ->assertHasErrors();
    }
}
