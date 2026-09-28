<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ExerciseVersionCacheTest extends TestCase
{
    use DatabaseTransactions;

    private function exercise(): Exercise
    {
        return Exercise::create([
            'name' => 'Ex',
            'decision_type' => ExerciseType::TRUE_FALSE->value,
            'clause' => [
                'sentence' => 'Здравей means hello.',
                'correct_option' => true,
                'explanation' => 'Test.',
            ],
        ]);
    }

    public function test_each_save_bumps_only_that_exercises_own_version(): void
    {
        $first = $this->exercise();
        $second = $this->exercise();

        $first->update(['name' => 'Renamed']);

        $this->assertSame(2, Cache::get("v:exercise:{$first->id}"));
        $this->assertSame(1, Cache::get("v:exercise:{$second->id}"));
        $this->assertNull(Cache::get('v:exercise:{$exercise->id}'));
    }
}
