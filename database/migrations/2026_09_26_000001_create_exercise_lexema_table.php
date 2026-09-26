<?php

use App\Models\Exercise;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * lexemas.exercise_id could only name the exercise that first introduced a
     * word, so a later exercise reusing it had no link at all and grading
     * skipped the word there. The pivot records every exercise a word appears
     * in. It is filled from both sources: each lexema's old exercise_id, and
     * the Cyrillic option words of every exercise matched to existing lexemas,
     * the same words Exercise::syncLexemasFromOptions() links from now on.
     * Exercises are read through Eloquent only to reuse the word extraction;
     * reading fires no observer. The old column goes once the pivot holds it.
     */
    public function up(): void
    {
        Schema::create('exercise_lexema', function (Blueprint $table) {
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lexema_id')->constrained()->cascadeOnDelete();
            $table->primary(['exercise_id', 'lexema_id']);
            $table->index('lexema_id');
        });

        DB::table('exercise_lexema')->insertUsing(
            ['exercise_id', 'lexema_id'],
            DB::table('lexemas')->whereNotNull('exercise_id')->select('exercise_id', 'id'),
        );

        Exercise::query()->lazyById()->each(function (Exercise $exercise) {
            $words = $exercise->cyrillicOptionWords();

            if ($words === []) {
                return;
            }

            DB::table('exercise_lexema')->insertOrIgnore(
                DB::table('lexemas')
                    ->whereIn('word', $words)
                    ->pluck('id')
                    ->map(fn ($id) => ['exercise_id' => $exercise->id, 'lexema_id' => $id])
                    ->all()
            );
        });

        Schema::table('lexemas', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['exercise_id']);
            }

            $table->dropColumn('exercise_id');
        });
    }

    /**
     * Restores the column with the lowest linked exercise id per lexema, the
     * closest the pivot gets to "the exercise that introduced it".
     */
    public function down(): void
    {
        Schema::table('lexemas', function (Blueprint $table) {
            $table->foreignId('exercise_id')->nullable()->after('word')->constrained()->nullOnDelete();
        });

        DB::table('lexemas')->update([
            'exercise_id' => DB::table('exercise_lexema')
                ->whereColumn('exercise_lexema.lexema_id', 'lexemas.id')
                ->selectRaw('min(exercise_id)'),
        ]);

        Schema::dropIfExists('exercise_lexema');
    }
};
