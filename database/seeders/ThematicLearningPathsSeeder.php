<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsLearningPaths;
use Illuminate\Database\Seeder;

/**
 * Nine topic paths, three each for A1, A2 and B1, of ten lessons with ten
 * exercises apiece, read from data/learning-paths/thematic. The topics follow
 * lessons 10-25 of Ivanova's "The Bulgarian Language in Practice" and its
 * appendices: the calendar, maths and numbers, Bulgaria and its history, the
 * tram and the taxi, money, trips and holidays, food, shops and restaurants,
 * illness and the body, sport, celebrations, art and education, travel and
 * impressions of the country. The exercises themselves are original; only the
 * topics follow the book.
 *
 * Every lesson has the same mix: two word-pair boards, five fill-ins and
 * three true/false questions. There is no image matching, because that needs
 * a picture per exercise.
 */
class ThematicLearningPathsSeeder extends Seeder
{
    use SeedsLearningPaths;

    public function run(): void
    {
        $this->seedPathsFrom(__DIR__.'/data/learning-paths/thematic');
    }
}
