<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const array TYPES = ['multiple_choice', 'true_false', 'fill_in_the_blank', 'image_matching'];

    /**
     * The scalar parts of a clause become columns of their own. `language_id`
     * is the language the exercise teaches and `translation_language_id` the
     * one a word-pair board translates it into; both are added nullable here
     * and made required by the backfill migration, the same sequencing the
     * role_id migration used. `prompt` is the sentence of the fill-in and
     * true/false types, `explanation` is common to every type, and
     * `statement_is_true` is the true/false answer.
     *
     * `clause` stays for now, no longer required, so the previous release can
     * still read what it wrote; a later migration drops it. The per-type CHECK
     * constraints that required its keys are dropped, since the rows they
     * guarded now carry their own constraints.
     */
    public function up(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->foreignId('language_id')->nullable()->after('decision_type')->constrained('languages');
            $table->foreignId('translation_language_id')->nullable()->after('language_id')->constrained('languages');
            $table->text('prompt')->nullable()->after('translation_language_id');
            $table->text('explanation')->default('')->after('prompt');
            $table->boolean('statement_is_true')->nullable()->after('explanation');
            $table->jsonb('clause')->nullable()->change();
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::TYPES as $type) {
            DB::statement("ALTER TABLE exercises DROP CONSTRAINT IF EXISTS exercises_clause_{$type}_check");
        }
    }

    /**
     * Reverse the migrations. The dropped CHECK constraints are not restored:
     * they read `clause`, which holds nothing newer than the day this ran.
     */
    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropConstrainedForeignId('translation_language_id');
            $table->dropConstrainedForeignId('language_id');
            $table->dropColumn(['prompt', 'explanation', 'statement_is_true']);
        });
    }
};
