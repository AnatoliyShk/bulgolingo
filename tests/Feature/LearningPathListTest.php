<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LearningPathListTest extends TestCase
{
    use DatabaseTransactions;

    private function pathWithLesson(User $user, string $name, int $exercises, bool $completeAll = false): LearningPath
    {
        $path = LearningPath::create(['name' => $name, 'language' => 'bg']);
        $path->users()->attach($user->id);

        $lesson = Lesson::create(['name' => "{$name} lesson", 'description' => 'D']);
        $path->lessons()->attach($lesson->id);

        $made = [];

        for ($i = 0; $i < $exercises; $i++) {
            $exercise = Exercise::create([
                'name' => "{$name}-{$i}",
                'decision_type' => ExerciseType::TRUE_FALSE->value,
                'clause' => ['sentence' => 'Здравей means hello.', 'correct_option' => true, 'explanation' => 'E.'],
            ]);
            $lesson->attachExerciseAtEnd($exercise);
            $made[] = $exercise;
        }

        if ($completeAll) {
            $user->completedExercises()->syncWithoutDetaching(collect($made)->pluck('id')->all());
        }

        return $path;
    }

    /**
     * Each list page fills only its own slice and sends the other one empty, so
     * a test names the prop it is reading rather than assuming which of the two
     * the route populated.
     */
    private function paths(string $route, string $prop = 'unfinishedPaths'): array
    {
        $props = null;

        $this->get(route($route))->assertOk()->assertInertia(
            function (Assert $page) use (&$props, $prop) {
                $page->component('LearningPath/List');
                $props = $page->toArray()['props'][$prop];
            }
        );

        return $props;
    }

    public function test_enrolled_lists_every_path_the_user_started(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $a = $this->pathWithLesson($user, 'A', 1);
        $b = $this->pathWithLesson($user, 'B', 1);

        $ids = collect($this->paths('learning-paths.enrolled'))->pluck('id')->all();

        $this->assertContains($a->id, $ids);
        $this->assertContains($b->id, $ids);
    }

    public function test_enrolled_excludes_paths_the_user_never_started(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $mine = $this->pathWithLesson($user, 'Mine', 1);
        LearningPath::create(['name' => 'Not mine', 'language' => 'bg']);

        $ids = collect($this->paths('learning-paths.enrolled'))->pluck('id')->all();

        $this->assertSame([$mine->id], $ids);
    }

    public function test_finished_only_lists_paths_where_every_lesson_is_done(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $done = $this->pathWithLesson($user, 'Done', 1, completeAll: true);
        $this->pathWithLesson($user, 'In progress', 1, completeAll: false);

        $props = $this->paths('learning-paths.finished', 'finishedPaths');

        $this->assertCount(1, $props);
        $this->assertSame($done->id, $props[0]['id']);
        $this->assertTrue($props[0]['is_finished']);
    }

    public function test_finished_is_empty_when_nothing_is_done_yet(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->pathWithLesson($user, 'In progress', 1, completeAll: false);

        $this->assertSame([], $this->paths('learning-paths.finished', 'finishedPaths'));
    }

    public function test_a_path_with_no_lessons_is_never_finished(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $path = LearningPath::create(['name' => 'Empty', 'language' => 'bg']);
        $path->users()->attach($user->id);

        $this->assertSame([], $this->paths('learning-paths.finished', 'finishedPaths'));

        $ids = collect($this->paths('learning-paths.enrolled'))->pluck('id')->all();
        $this->assertContains($path->id, $ids);
    }

    /**
     * The two pages were the same page: enrolled() built both slices and
     * finished() called straight through to it, so the profile's two links led
     * to identical lists. Each of these asserts the slice its page must not be
     * carrying, which is the half the older tests never looked at.
     */
    public function test_enrolled_carries_no_finished_paths(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->pathWithLesson($user, 'Done', 1, completeAll: true);
        $this->pathWithLesson($user, 'Doing', 1);

        $this->assertSame([], $this->paths('learning-paths.enrolled', 'finishedPaths'));
        $this->assertCount(1, $this->paths('learning-paths.enrolled'));
    }

    public function test_finished_carries_no_unfinished_paths(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->pathWithLesson($user, 'Done', 1, completeAll: true);
        $this->pathWithLesson($user, 'Doing', 1);

        $this->assertSame([], $this->paths('learning-paths.finished'));
        $this->assertCount(1, $this->paths('learning-paths.finished', 'finishedPaths'));
    }

    public function test_the_two_lists_do_not_show_the_same_paths(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $done = $this->pathWithLesson($user, 'Done', 1, completeAll: true);
        $doing = $this->pathWithLesson($user, 'Doing', 1);

        $enrolledIds = collect($this->paths('learning-paths.enrolled'))->pluck('id')->all();
        $finishedIds = collect($this->paths('learning-paths.finished', 'finishedPaths'))->pluck('id')->all();

        $this->assertSame([$doing->id], $enrolledIds);
        $this->assertSame([$done->id], $finishedIds);
        $this->assertSame([], array_intersect($enrolledIds, $finishedIds));
    }

    public function test_guest_cannot_view_enrolled_or_finished_paths(): void
    {
        $this->get(route('learning-paths.enrolled'))->assertRedirect(route('login'));
        $this->get(route('learning-paths.finished'))->assertRedirect(route('login'));
    }
}
