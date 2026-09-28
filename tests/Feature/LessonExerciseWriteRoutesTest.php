<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Lessons and exercises are authored only through the admin panel. The
 * student-side lesson.* and exercise.* routes are playing a lesson and
 * nothing more, so an authoring route cannot reappear there unguarded or
 * half-built next to the admin one.
 */
class LessonExerciseWriteRoutesTest extends TestCase
{
    use DatabaseTransactions;

    // Pins the whole student-side set, so adding any route to either group,
    // write or not, has to be a deliberate change to this list.
    public function test_the_student_side_routes_only_play_lessons(): void
    {
        $names = collect(Route::getRoutes()->getRoutes())
            ->map(fn (RoutingRoute $route) => $route->getName())
            ->filter(fn (?string $name) => $name !== null && preg_match('/^(lesson|exercise)\./', $name))
            ->sort()
            ->values()
            ->all();

        $this->assertSame([
            'exercise.complete',
            'exercise.show',
            'lesson.complete',
            'lesson.restart',
            'lesson.show',
        ], $names);
    }

    // The old authoring URLs no longer reach a controller. The show routes
    // take numeric ids only, so the create forms and any other non-numeric
    // segment are a 404 rather than a bigint lookup Postgres would fail with a
    // 500; the bare collection URL matches nothing, and a write to a real
    // record's URL is refused by method.
    public function test_the_old_authoring_urls_do_nothing(): void
    {
        $admin = User::factory()->admin()->create();
        $lesson = Lesson::create(['name' => 'Greetings', 'description' => 'D']);
        $exercise = Exercise::create([
            'name' => 'Ex',
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => ['sentence' => 'Здравей means hello.', 'correct_option' => true, 'explanation' => 'Test.'],
        ]);
        $lesson->attachExerciseAtEnd($exercise);

        $this->actingAs($admin)->get('/lesson/create')->assertNotFound();
        $this->actingAs($admin)->get('/exercise/create')->assertNotFound();
        $this->actingAs($admin)->get('/lesson/greetings/complete')->assertNotFound();
        $this->actingAs($admin)->post('/lesson', ['name' => 'N', 'description' => 'D'])->assertNotFound();
        $this->actingAs($admin)->delete("/lesson/{$lesson->id}")->assertMethodNotAllowed();
        $this->actingAs($admin)->put("/exercise/{$exercise->id}", ['name' => 'N'])->assertMethodNotAllowed();

        $this->assertModelExists($lesson);
        $this->assertSame(1, Lesson::count());
        $this->assertSame('Ex', $exercise->fresh()->name);
    }
}
