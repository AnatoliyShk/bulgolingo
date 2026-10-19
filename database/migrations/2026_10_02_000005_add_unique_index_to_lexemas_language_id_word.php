<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The same spelling is a different word in another language, so a word is
     * unique per language rather than across the whole table. The old unique
     * index on `word` alone is stricter, so no existing row can break the
     * new one.
     */
    public function up(): void
    {
        Schema::table('lexemas', function (Blueprint $table) {
            $table->dropUnique(['word']);
            $table->unique(['language_id', 'word']);
        });
    }

    /**
     * Reverse the migrations. A word kept in two languages cannot go back
     * under a unique index on the word alone, and this stops with the
     * database's own error rather than deleting a lexema that learners'
     * review history points at.
     */
    public function down(): void
    {
        Schema::table('lexemas', function (Blueprint $table) {
            $table->dropUnique(['language_id', 'word']);
            $table->unique('word');
        });
    }
};
