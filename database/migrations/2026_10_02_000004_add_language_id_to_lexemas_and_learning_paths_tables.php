<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A lexema is a word in a language, so it gets a `language_id`. Every
     * lexema so far was extracted by the Cyrillic filter, which makes them all
     * Bulgarian; the column is added nullable, backfilled and then made
     * required, as the role_id migration did.
     *
     * A learning path gets a `language_id` beside its free-text `language`
     * ('BG' in the data), matched case-insensitively against `languages.code`.
     * A path whose text matches no language is left null. The text column
     * stays until a later migration drops it, and LearningPath keeps the two
     * in step in the meantime.
     */
    public function up(): void
    {
        Schema::table('lexemas', function (Blueprint $table) {
            $table->foreignId('language_id')->nullable()->after('word')->constrained('languages');
        });

        Schema::table('learning_paths', function (Blueprint $table) {
            $table->foreignId('language_id')->nullable()->after('language')->constrained('languages');
        });

        $languages = DB::table('languages')->pluck('id', 'code');

        DB::table('lexemas')->update(['language_id' => $languages['bg']]);

        foreach ($languages as $code => $id) {
            DB::table('learning_paths')->whereRaw('lower(language) = ?', [$code])->update(['language_id' => $id]);
        }

        Schema::table('lexemas', function (Blueprint $table) {
            $table->foreignId('language_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_paths', function (Blueprint $table) {
            $table->dropConstrainedForeignId('language_id');
        });

        Schema::table('lexemas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('language_id');
        });
    }
};
