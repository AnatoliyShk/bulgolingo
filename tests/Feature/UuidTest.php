<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Enums\RoleName;
use App\Enums\UserType;
use App\Models\Bot;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\Lexema;
use App\Models\Role;
use App\Models\ScriptedDialogue;
use App\Models\ScriptedLine;
use App\Models\Type;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class UuidTest extends TestCase
{
    use DatabaseTransactions;

    public function test_creating_a_row_of_every_uuid_carrying_table_stamps_a_v7_uuid(): void
    {
        $user = User::factory()->create();
        $path = LearningPath::create(['name' => 'Greetings', 'language' => 'bg']);
        $lesson = Lesson::create(['name' => 'L', 'description' => 'D']);
        $exercise = Exercise::create([
            'name' => 'Ex',
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => [
                'sentence' => 'Здравей means hello.',
                'correct_option' => true,
                'explanation' => 'Greeting.',
            ],
        ]);

        $dialogue = ScriptedDialogue::create([
            'bot_id' => Bot::create(['name' => 'Ivan', 'description' => 'Shopkeeper'])->id,
            'user_id' => $user->id,
        ]);
        $line = ScriptedLine::create([
            'scripted_dialogue_id' => $dialogue->id,
            'clause' => ['text' => 'Здравей!'],
        ]);
        $lexema = Lexema::create(['word' => 'здравей', 'exercise_id' => $exercise->id]);

        foreach ([$user, $path, $lesson, $exercise, $dialogue, $line, $lexema] as $model) {
            $this->assertNotNull($model->uuid);
            $this->assertSame(7, Uuid::fromString($model->uuid)->getFields()->getVersion());
        }
    }

    /**
     * Rolls the NOT NULL migration back so rows can be written without a uuid
     * through the query builder, which skips HasUuidV7's creating hook the way
     * rows predating the column did, then runs it forward and expects each of
     * them stamped with a v7 uuid. Postgres runs the DDL inside the test's
     * transaction, so the schema is restored when the test ends.
     */
    public function test_the_not_null_migration_fills_rows_missing_a_uuid(): void
    {
        $migration = require database_path('migrations/2026_09_25_000002_make_uuid_columns_not_null.php');
        $migration->down();

        $userId = DB::table('users')->insertGetId([
            'name' => 'Legacy',
            'email' => 'legacy-uuid@example.com',
            'password' => 'hash',
            'role_id' => Role::named(RoleName::Student)->id,
            'type_id' => Type::named(UserType::Regular)->id,
        ]);
        $pathId = DB::table('learning_paths')->insertGetId(['name' => 'Legacy', 'language' => 'bg']);
        $lessonId = DB::table('lessons')->insertGetId(['name' => 'Legacy', 'description' => 'D']);
        $exerciseId = DB::table('exercises')->insertGetId([
            'name' => 'Legacy',
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => json_encode([
                'sentence' => 'Здравей means hello.',
                'correct_option' => true,
                'explanation' => 'Greeting.',
            ]),
        ]);

        $botId = DB::table('bots')->insertGetId(['name' => 'Legacy', 'description' => 'D']);
        $dialogueId = DB::table('scripted_dialogues')->insertGetId([
            'bot_id' => $botId,
            'user_id' => $userId,
        ]);
        $lineId = DB::table('scripted_lines')->insertGetId([
            'scripted_dialogue_id' => $dialogueId,
            'clause' => json_encode(['text' => 'Здравей!']),
        ]);
        $lexemaId = DB::table('lexemas')->insertGetId([
            'word' => 'легаси',
            'exercise_id' => $exerciseId,
        ]);

        $migration->up();

        $ids = [
            'users' => $userId,
            'learning_paths' => $pathId,
            'lessons' => $lessonId,
            'exercises' => $exerciseId,
            'scripted_dialogues' => $dialogueId,
            'scripted_lines' => $lineId,
            'lexemas' => $lexemaId,
        ];

        foreach ($ids as $table => $id) {
            $uuid = DB::table($table)->find($id)->uuid;
            $this->assertNotNull($uuid, $table);
            $this->assertSame(7, Uuid::fromString($uuid)->getFields()->getVersion());
        }
    }

    public function test_the_not_null_migration_leaves_an_already_stamped_uuid_untouched(): void
    {
        $path = LearningPath::create(['name' => 'Fresh', 'language' => 'bg']);

        $migration = require database_path('migrations/2026_09_25_000002_make_uuid_columns_not_null.php');
        $migration->down();
        $migration->up();

        $this->assertSame($path->uuid, DB::table('learning_paths')->find($path->id)->uuid);
    }

    public function test_a_row_without_a_uuid_is_rejected(): void
    {
        $this->expectException(QueryException::class);

        DB::table('lessons')->insert(['name' => 'No uuid', 'description' => 'D']);
    }
}
