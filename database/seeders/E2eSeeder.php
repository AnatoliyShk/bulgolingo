<?php

namespace Database\Seeders;

use App\Enums\ExerciseType;
use App\Enums\LearningPathType;
use App\Enums\RoleName;
use App\Models\Exercise;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds everything the Playwright specs look for: the admin, student,
 * unverified and admin visitor logins, the catalog from the repo's own path
 * seeders (none of which call an AI provider), and some progress for the
 * student — enrolled in the first path with its first lesson finished, which
 * also earns experience and a streak. Filler paths of the test tier push the
 * admin index past one page, which only admins can see, so the student
 * catalog is unchanged. Idempotent, so it can be re-run
 * against an existing e2e database. fixtureEnv() names the rows the specs
 * take ids of.
 */
class E2eSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'e2e-admin@example.com';

    public const STUDENT_EMAIL = 'e2e-stats@example.com';

    public const UNVERIFIED_EMAIL = 'e2e-unverified@example.com';

    public const PAGINATION_FILLER_PATHS = 10;

    public function run(): void
    {
        $this->seedUser(self::ADMIN_EMAIL, 'E2E Admin', RoleName::Admin);
        $student = $this->seedUser(self::STUDENT_EMAIL, 'E2E Student', RoleName::Student);
        $this->seedUser(self::UNVERIFIED_EMAIL, 'E2E Unverified', RoleName::Student, verified: false);

        $this->call([
            AdminVisitorSeeder::class,
            CefrLearningPathsSeeder::class,
            ThematicLearningPathsSeeder::class,
        ]);

        $this->seedPaginationFillers();
        $this->finishFirstLesson($student);
    }

    /**
     * The ids the specs read from E2E_* variables, keyed by variable name. The
     * lesson taking new exercises and the word pair exercise to edit are kept
     * out of the finished lesson, so the specs using them cannot undo the
     * progress the restart spec needs.
     *
     * @return array<string, string>
     */
    public static function fixtureEnv(): array
    {
        $completed = self::completedLesson();
        $otherLesson = self::firstPath()->lessons()->orderBy('lessons.id')
            ->where('lessons.id', '!=', $completed->id)->first();

        $wordPair = Exercise::where('decision_type', ExerciseType::MULTIPLE_CHOICE)
            ->whereDoesntHave('lessons', fn ($q) => $q->where('lessons.id', $completed->id))
            ->orderBy('id')->first();

        return [
            'E2E_ADMIN_EMAIL' => self::ADMIN_EMAIL,
            'E2E_USER_EMAIL' => self::STUDENT_EMAIL,
            'E2E_UNVERIFIED_USER_EMAIL' => self::UNVERIFIED_EMAIL,
            'E2E_COMPLETED_LESSON_ID' => (string) $completed->id,
            'E2E_LESSON_ID' => (string) $otherLesson?->id,
            'E2E_WORD_PAIR_EXERCISE_ID' => (string) $wordPair?->id,
        ];
    }

    private function seedUser(string $email, string $name, RoleName $role, bool $verified = true): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => 'password',
                'role_id' => Role::named($role)->id,
                'email_verified_at' => $verified ? now() : null,
            ]
        );
    }

    private function seedPaginationFillers(): void
    {
        foreach (range(1, self::PAGINATION_FILLER_PATHS) as $number) {
            LearningPath::firstOrCreate(
                ['name' => "E2E pagination filler {$number}"],
                ['language' => 'BG', 'type' => LearningPathType::Test],
            );
        }
    }

    /**
     * Goes through the same steps as a student answering every exercise of the
     * lesson, so experience, the streak and the stats caches follow as they
     * would in the app.
     */
    private function finishFirstLesson(User $student): void
    {
        $path = self::firstPath();
        $lesson = self::completedLesson();

        $student->learningPaths()->syncWithoutDetaching([$path->id]);

        foreach ($lesson->exercises as $exercise) {
            $exercise->completeFor($student, null);
        }

        $student->recordPractice();

        DB::table('learning_path_lesson')
            ->where('learning_path_id', $path->id)
            ->where('lesson_id', $lesson->id)
            ->update(['is_completed' => true]);
    }

    private static function firstPath(): LearningPath
    {
        return LearningPath::orderBy('id')->firstOrFail();
    }

    private static function completedLesson(): Lesson
    {
        return self::firstPath()->lessons()->orderBy('lessons.id')->firstOrFail();
    }
}
