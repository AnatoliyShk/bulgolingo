<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BackfillUuids extends Command
{
    protected $signature = 'uuid:backfill';

    protected $description = 'Fill the uuid column added to users, learning_paths, lessons, and exercises for every row that predates it';

    /**
     * Writes through the query builder rather than loading Eloquent models:
     * Exercise's observer reshuffles word-pair order and re-validates the
     * clause on every save, side effects a column-only backfill must not
     * trigger. New rows get their uuid from App\Models\Concerns\HasUuidV7
     * on create, so this command only ever touches pre-existing ones.
     */
    public function handle(): int
    {
        foreach (['users', 'learning_paths', 'lessons', 'exercises'] as $table) {
            $this->backfill($table);
        }

        return self::SUCCESS;
    }

    private function backfill(string $table): void
    {
        $count = DB::table($table)->whereNull('uuid')->count();

        if ($count === 0) {
            $this->info("{$table}: nothing to backfill.");

            return;
        }

        $this->info("{$table}: backfilling {$count} rows.");
        $bar = $this->output->createProgressBar($count);

        DB::table($table)->whereNull('uuid')->chunkById(1000, function ($rows) use ($table, $bar) {
            foreach ($rows as $row) {
                DB::table($table)->where('id', $row->id)->update(['uuid' => (string) Str::uuid7()]);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
    }
}
