<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsLearningPaths;
use Illuminate\Database\Seeder;

/**
 * One path per CEFR level, A1 to C2, read from
 * data/learning-paths/cefr. Every level runs through the same ten themes,
 * taken from the sequence of lessons 1-10 in Ivanova's "The Bulgarian
 * Language in Practice": meeting people, introductions, family, numbers and
 * routine, home, guests, work, the natural world, time and weather, seasons
 * and holidays. Each level revisits them with the grammar and register that
 * suit it. The exercises themselves are original; only the themes and the
 * order the grammar is introduced follow the book.
 */
class CefrLearningPathsSeeder extends Seeder
{
    use SeedsLearningPaths;

    public function run(): void
    {
        $this->seedPathsFrom(__DIR__.'/data/learning-paths/cefr');
    }
}
