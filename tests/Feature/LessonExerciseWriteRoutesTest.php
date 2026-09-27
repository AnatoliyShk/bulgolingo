<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LessonExerciseWriteRoutesTest extends TestCase
{
    use DatabaseTransactions;

    private Lesson $lesson;

    private Exercise $exercise;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lesson = Lesson::create(['name' => 'Greetings', 'description' => 'D']);

        $this->exercise = Exercise::create([
            'name' => 'Ex',
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => [
                'sentence' => 'Здравей means hello.',
                'correct_option' => true,
                'explanation' => 'Test.',
            ],
        ]);

        $this->lesson->attachExerciseAtEnd($this->exercise);
    }

    /**
     * Every create, update and delete route of the student-side lesson and
     * exercise controllers, as [method, route name, which model it binds].
     *
     * @return array<string, array{string, string, ?string}>
     */
    public static function writeRoutes(): array
    {
        return [
            'lesson.create' => ['get', 'lesson.create', null],
            'lesson.store' => ['post', 'lesson.store', null],
            'lesson.edit' => ['get', 'lesson.edit', 'lesson'],
            'lesson.update' => ['put', 'lesson.update', 'lesson'],
            'lesson.destroy' => ['delete', 'lesson.destroy', 'lesson'],
            'exercise.create' => ['get', 'exercise.create', null],
            'exercise.store' => ['post', 'exercise.store', null],
            'exercise.edit' => ['get', 'exercise.edit', 'exercise'],
            'exercise.update' => ['put', 'exercise.update', 'exercise'],
            'exercise.destroy' => ['delete', 'exercise.destroy', 'exercise'],
        ];
    }

    private function url(string $name, ?string $binds): string
    {
        return route($name, $binds ? $this->{$binds} : []);
    }

    #[DataProvider('writeRoutes')]
    public function test_a_guest_is_sent_to_login(string $method, string $name, ?string $binds): void
    {
        $this->{$method}($this->url($name, $binds))->assertRedirect(route('login'));
    }

    #[DataProvider('writeRoutes')]
    public function test_a_student_is_forbidden(string $method, string $name, ?string $binds): void
    {
        $this->actingAs(User::factory()->create())
            ->{$method}($this->url($name, $binds))
            ->assertForbidden();
    }

    public function test_an_admin_visitor_cannot_delete_a_lesson(): void
    {
        $this->actingAs(User::factory()->adminVisitor()->create())
            ->delete(route('lesson.destroy', $this->lesson))
            ->assertForbidden();

        $this->assertModelExists($this->lesson);
    }

    public function test_an_admin_can_delete_a_lesson(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('lesson.destroy', $this->lesson))
            ->assertSuccessful();

        $this->assertModelMissing($this->lesson);
    }
}
