<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A language is a row, not a code constant: exercises and lexemas point at
     * it, so teaching another language is one more row here and no code or
     * schema change. `unicode_script` is the Unicode script name PCRE understands
     * (`\p{Cyrillic}`), which is how the words of a language are told apart
     * from the words of the one it is translated into. The rows are inserted
     * here, as roles and types are, and the defaults are the two languages the
     * content has so far.
     */
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->string('name');
            $table->string('unicode_script', 32);
            $table->timestamps();
        });

        $now = now();

        DB::table('languages')->insert([
            ['code' => 'bg', 'name' => 'Bulgarian', 'unicode_script' => 'Cyrillic', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'en', 'name' => 'English', 'unicode_script' => 'Latin', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
