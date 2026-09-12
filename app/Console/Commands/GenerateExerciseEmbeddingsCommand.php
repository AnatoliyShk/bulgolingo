<?php

namespace App\Console\Commands;

use App\Jobs\GenerateExerciseEmbedding;
use App\Models\Exercise;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:generate-exercise-embeddings-command {--force : Re-embed exercises that already have an embedding}')]
#[Description('Queue embedding generation for exercises that have none')]
class GenerateExerciseEmbeddingsCommand extends Command
{
    public function handle(): void
    {
        $force = $this->option('force');

        $query = Exercise::query();

        if (! $force) {
            $query->whereNull('embedding');
        }

        $total = $query->count();

        if ($total === 0) {
            $this->info('All exercises already have embeddings.');
            return;
        }

        $this->info("Exercises to process: {$total}");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById(100, function ($exercises) use ($bar) {
            foreach ($exercises as $exercise) {
                GenerateExerciseEmbedding::dispatch($exercise);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Queued {$total} embedding jobs. Make sure a queue worker is running (php artisan queue:work).");
    }
}
