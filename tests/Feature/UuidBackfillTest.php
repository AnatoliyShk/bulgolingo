<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Enums\RoleName;
use App\Models\Bot;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\ScriptedDialogue;
use App\Models\ScriptedLine;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class UuidBackfillTest extends TestCase
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

        foreach ([$user, $path, $lesson, $exercise, $dialogue, $line] as $model) {
            $this->assertNotNull($model->uuid);
            $this->assertSame(7, Uuid::fromString($model->uuid)->getFields()->getVersion());
        }
    }

    /**
     * Rows created directly through the query builder skip HasUuidV7's
     * creating hook entirely, standing in for rows that existed before the
     * column was added — exactly what the command is meant to backfill.
     */
    public function test_backfill_command_fills_rows_missing_a_uuid(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Legacy',
            'email' => 'legacy-uuid@example.com',
            'password' => 'hash',
            'role_id' => Role::named(RoleName::Student)->id,
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

        $this->artisan('uuid:backfill')->assertSuccessful();

        $user = DB::table('users')->find($userId);
        $path = DB::table('learning_paths')->find($pathId);
        $lesson = DB::table('lessons')->find($lessonId);
        $exercise = DB::table('exercises')->find($exerciseId);
        $dialogue = DB::table('scripted_dialogues')->find($dialogueId);
        $line = DB::table('scripted_lines')->find($lineId);

        foreach ([$user->uuid, $path->uuid, $lesson->uuid, $exercise->uuid, $dialogue->uuid, $line->uuid] as $uuid) {
            $this->assertNotNull($uuid);
            $this->assertSame(7, Uuid::fromString($uuid)->getFields()->getVersion());
        }
    }

    public function test_backfill_command_leaves_an_already_stamped_uuid_untouched(): void
    {
        $path = LearningPath::create(['name' => 'Fresh', 'language' => 'bg']);

        $this->artisan('uuid:backfill')->assertSuccessful();

        $this->assertSame($path->uuid, DB::table('learning_paths')->find($path->id)->uuid);
    }
}
