<?php

namespace Tests\Feature;

use App\Enums\LanguageLevel;
use App\Enums\LearningPathType;
use App\Http\Controllers\LexemaController;
use App\Http\Controllers\ScriptedDialogueController;
use App\Http\Controllers\ScriptedLineController;
use App\Models\Bot;
use App\Models\Images;
use App\Models\LearningPath;
use App\Models\Lexema;
use App\Models\ScriptedDialogue;
use App\Models\ScriptedLine;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The content policies follow the admin panel's access rules: admins read and
 * write, admin visitors only read, students do neither. Learning paths add a
 * student-facing `view` on top, decided by the catalog's type visibility.
 */
class ContentPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        return match ($role) {
            'admin' => User::factory()->admin()->create(),
            'admin_visitor' => User::factory()->adminVisitor()->create(),
            'student' => User::factory()->create(),
        };
    }

    private function learningPath(LearningPathType $type): LearningPath
    {
        return LearningPath::create([
            'name' => $type->value.' path',
            'language' => 'bg',
            'type' => $type->value,
            'level' => LanguageLevel::A2,
        ]);
    }

    private function scriptedDialogue(): ScriptedDialogue
    {
        return ScriptedDialogue::create([
            'bot_id' => Bot::create(['name' => 'Ivan', 'description' => 'Baker'])->id,
            'user_id' => User::factory()->admin()->create()->id,
        ]);
    }

    /**
     * One saved record of the class. Most of these models' factories are
     * still empty stubs, so each is built by hand as the admin tests do.
     *
     * @param  class-string<Model>  $class
     */
    private function makeModel(string $class): Model
    {
        return match ($class) {
            Images::class => Images::create(['filepath' => 'exercise-images/policy.png']),
            LearningPath::class => $this->learningPath(LearningPathType::Regular),
            Lexema::class => Lexema::factory()->create(),
            ScriptedDialogue::class => $this->scriptedDialogue(),
            ScriptedLine::class => ScriptedLine::create([
                'scripted_dialogue_id' => $this->scriptedDialogue()->id,
                'clause' => ['line_text' => 'Добър ден!', 'options' => ['a', 'b', 'c'], 'correct_option' => 0],
            ]),
        };
    }

    /**
     * @return array<string, array{string, bool, bool}>
     */
    public static function roles(): array
    {
        return [
            'admin reads and writes' => ['admin', true, true],
            'admin visitor only reads' => ['admin_visitor', true, false],
            'student does neither' => ['student', false, false],
        ];
    }

    /**
     * Every model under every role, as `[class, role, canRead, canWrite]`.
     *
     * @return array<string, array{class-string<Model>, string, bool, bool}>
     */
    public static function modelsAndRoles(): array
    {
        $cases = [];

        foreach ([Images::class, LearningPath::class, Lexema::class, ScriptedDialogue::class, ScriptedLine::class] as $class) {
            foreach (self::roles() as $label => $row) {
                $cases[class_basename($class).': '.$label] = [$class, ...$row];
            }
        }

        return $cases;
    }

    /**
     * Learning paths are left out of the `view` check here, since theirs
     * follows the catalog rather than the panel and has its own test below.
     */
    #[DataProvider('modelsAndRoles')]
    public function test_the_policy_follows_the_admin_panel_rules(string $class, string $role, bool $canRead, bool $canWrite): void
    {
        $user = $this->userWithRole($role);
        $model = $this->makeModel($class);

        $this->assertSame($canRead, $user->can('viewAny', $class));
        $this->assertSame($canWrite, $user->can('create', $class));
        $this->assertSame($canWrite, $user->can('update', $model));
        $this->assertSame($canWrite, $user->can('delete', $model));

        if ($class !== LearningPath::class) {
            $this->assertSame($canRead, $user->can('view', $model));
        }
    }

    /**
     * A student sees only regular paths, and so does an admin visitor, who is
     * not an admin for the catalog; only an admin sees the `test` tier. A
     * hidden path is refused as a 404, not a 403.
     */
    public function test_viewing_a_learning_path_follows_the_catalog_visibility(): void
    {
        $regular = $this->learningPath(LearningPathType::Regular);
        $test = $this->learningPath(LearningPathType::Test);

        $this->assertTrue(Gate::forUser(null)->allows('view', $regular));
        $this->assertTrue($this->userWithRole('student')->can('view', $regular));
        $this->assertTrue($this->userWithRole('admin')->can('view', $test));

        $denied = Gate::forUser($this->userWithRole('admin_visitor'))->inspect('view', $test);
        $this->assertTrue($denied->denied());
        $this->assertSame(404, $denied->status());
        $this->assertSame(404, Gate::forUser(null)->inspect('view', $test)->status());
    }

    /**
     * The admin forms take `viewAny`, not `view`, so an admin visitor can
     * still open a `test` path's edit page, which the catalog's `view` would
     * turn into a 404.
     */
    public function test_an_admin_visitor_still_opens_the_admin_forms(): void
    {
        $this->actingAs($this->userWithRole('admin_visitor'));

        $this->get(route('admin.learning-paths.create'))->assertOk();
        $this->get(route('admin.learning-paths.edit', $this->learningPath(LearningPathType::Test)))->assertOk();
        $this->get(route('admin.scripted-dialogues.edit', $this->scriptedDialogue()))->assertOk();
        $this->get(route('admin.scripted-lines.edit', $this->makeModel(ScriptedLine::class)))->assertOk();
    }

    /**
     * The attributes on `update` and `destroy` name the route parameter the
     * model is bound to, which `Route::resource()` spells in snake case. A
     * misspelt name hands the policy a string and refuses even an admin, so
     * each admin delete goes through its real route here.
     */
    public function test_an_admin_still_deletes_through_the_admin_routes(): void
    {
        $this->actingAs($this->userWithRole('admin'));

        foreach ([
            'admin.learning-paths.destroy' => LearningPath::class,
            'admin.scripted-dialogues.destroy' => ScriptedDialogue::class,
            'admin.scripted-lines.destroy' => ScriptedLine::class,
        ] as $route => $class) {
            $model = $this->makeModel($class);

            $this->delete(route($route, $model))->assertRedirect();
            $this->assertModelMissing($model);
        }
    }

    /**
     * The unrouted scaffold controllers, as `[controller, model, resource]`.
     *
     * @return array<string, array{class-string, class-string<Model>, string, string, bool, bool}>
     */
    public static function scaffolds(): array
    {
        $cases = [];

        foreach ([
            [LexemaController::class, Lexema::class, 'lexemas'],
            [ScriptedDialogueController::class, ScriptedDialogue::class, 'scripted-dialogues'],
            [ScriptedLineController::class, ScriptedLine::class, 'scripted-lines'],
        ] as [$controller, $class, $resource]) {
            foreach (self::roles() as $label => $row) {
                $cases[class_basename($controller).': '.$label] = [$controller, $class, $resource, ...$row];
            }
        }

        return $cases;
    }

    /**
     * The scaffolds are not routed yet, so the test routes each as the
     * resource it would become, with the parameter name `Route::resource()`
     * gives it, to prove every action's `#[Authorize]` attribute reaches the
     * policy: a permitted call gets the empty scaffold's 200, a refused one a
     * 403.
     */
    #[DataProvider('scaffolds')]
    public function test_the_scaffold_actions_are_guarded_by_the_policy(string $controller, string $class, string $resource, string $role, bool $canRead, bool $canWrite): void
    {
        Route::middleware(['web', 'auth'])->resource($resource, $controller);

        $id = $this->makeModel($class)->id;
        $read = $canRead ? 200 : 403;
        $write = $canWrite ? 200 : 403;

        $this->actingAs($this->userWithRole($role));

        $this->get("/{$resource}")->assertStatus($read);
        $this->get("/{$resource}/{$id}")->assertStatus($read);
        $this->get("/{$resource}/create")->assertStatus($write);
        $this->post("/{$resource}")->assertStatus($write);
        $this->get("/{$resource}/{$id}/edit")->assertStatus($write);
        $this->put("/{$resource}/{$id}")->assertStatus($write);
        $this->delete("/{$resource}/{$id}")->assertStatus($write);
    }
}
