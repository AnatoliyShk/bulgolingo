<?php

namespace Tests\Feature;

use App\Enums\Rating;
use App\Models\Lexema;
use App\Models\ReviewLog;
use App\Models\User;
use App\Models\UserLexema;
use App\Services\GradeLexemeReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeLexemeReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_review_creates_a_user_lexema_row_and_a_review_log(): void
    {
        $user = User::factory()->create();
        $lexema = Lexema::factory()->create(['word' => 'куче']);

        app(GradeLexemeReview::class)->grade($user, $lexema, isCorrect: true, hintUsed: false, responseMs: 1200);

        $row = UserLexema::query()
            ->where('user_id', $user->id)
            ->where('lexema_id', $lexema->id)
            ->first();

        $this->assertNotNull($row);
        $this->assertSame(1, $row->reps_total);
        $this->assertSame(0, $row->lapses);
        $this->assertNotNull($row->due_at);
        $this->assertNotNull($row->last_reviewed_at);
        $this->assertGreaterThan(0, $row->stability);

        $log = ReviewLog::query()->where('user_id', $user->id)->where('lexema_id', $lexema->id)->first();
        $this->assertNotNull($log);
        $this->assertSame(Rating::Easy->value, $log->rating);
        $this->assertNull($log->stability_before);
        $this->assertSame('fsrs-6', $log->scheduler);
    }

    public function test_an_incorrect_answer_increments_lapses_on_a_repeat_review(): void
    {
        $user = User::factory()->create();
        $lexema = Lexema::factory()->create(['word' => 'котка']);

        $service = app(GradeLexemeReview::class);
        $service->grade($user, $lexema, isCorrect: true, hintUsed: false, responseMs: 1000);
        $service->grade($user, $lexema, isCorrect: false, hintUsed: false, responseMs: 4000);

        $row = UserLexema::query()
            ->where('user_id', $user->id)
            ->where('lexema_id', $lexema->id)
            ->first();

        $this->assertSame(2, $row->reps_total);
        $this->assertSame(1, $row->lapses);

        $this->assertSame(2, ReviewLog::query()->count());
    }
}
