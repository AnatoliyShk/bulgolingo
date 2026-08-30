<?php

namespace App\Console\Commands;

use App\Models\Exercise;
use Illuminate\Console\Command;

class BackfillLexemasFromExerciseOptions extends Command
{
    protected $signature = 'lexemas:backfill-from-options';

    protected $description = 'Create a lexema for every Cyrillic word in every existing exercise\'s clause options';

    public function handle(): int
    {
        $count = Exercise::count();
        $bar = $this->output->createProgressBar($count);

        Exercise::query()->lazyById()->each(function (Exercise $exercise) use ($bar) {
            $exercise->syncLexemasFromOptions();
            $bar->advance();
        });

        $bar->finish();
        $this->newLine();

        return self::SUCCESS;
    }
}
