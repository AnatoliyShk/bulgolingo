<?php

namespace Database\Seeders\Cefr;

use App\Enums\LanguageLevel;
use Database\Seeders\Concerns\SeedsLearningPaths;
use Illuminate\Database\Seeder;

/**
 * One CEFR level's path. Every level runs through the same ten themes, taken
 * from the sequence of lessons 1-10 in Ivanova's "The Bulgarian Language in
 * Practice": meeting people, introductions, family, numbers and routine, home,
 * guests, work, the natural world, time and weather, seasons and holidays.
 * Each level revisits them with the grammar and register that suit it. The
 * exercises themselves are original; only the themes and the order the
 * grammar is introduced follow the book.
 */
abstract class CefrPathSeeder extends Seeder
{
    use SeedsLearningPaths;

    abstract protected function level(): LanguageLevel;

    abstract protected function pathName(): string;

    /**
     * @return array<int, array{name: string, description: string, exercises: array<int, array>}>
     */
    abstract protected function lessons(): array;

    public function run(): void
    {
        $this->seedPath($this->pathName(), $this->level(), $this->lessons());
    }
}
