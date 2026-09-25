<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const TABLES = ['users', 'learning_paths', 'lessons', 'exercises', 'scripted_dialogues', 'scripted_lines', 'lexemas'];

    /**
     * Run the migrations.
     *
     * The uuid columns were added nullable so the `uuid:backfill` command could
     * fill pre-existing rows later. Now that the API addresses records by uuid,
     * a row without one is unreachable, so the column becomes a real candidate
     * key. Any row still missing a uuid gets a v7 one first, written through
     * the query builder so Exercise's saving hook does not reshuffle or
     * re-validate clauses, and then the column is made NOT NULL.
     */
    public function up(): void
    {
        foreach (self::TABLES as $name) {
            DB::table($name)->whereNull('uuid')->chunkById(1000, function ($rows) use ($name) {
                foreach ($rows as $row) {
                    DB::table($name)->where('id', $row->id)->update(['uuid' => (string) Str::uuid7()]);
                }
            });

            Schema::table($name, function (Blueprint $table) {
                $table->uuid('uuid')->nullable(false)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->change();
            });
        }
    }
};
