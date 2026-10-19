<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The rows an exercise's clause used to hold as JSON arrays.
     *
     * `exercise_options` is the answer list of the fill-in and image-matching
     * types: `position` is the option's place in the list and `is_correct`
     * replaces the clause's positional `correct_option` index. Nothing here is
     * one-correct-only, so a type with several right answers needs no schema
     * change.
     *
     * `exercise_word_pairs` is the word-pair board. `term` is in the exercise's
     * own language and `translation` in its translation language, so which
     * side is which is data. `left_position` and `right_position` are the
     * pair's place in each column of the board, both null while no board order
     * is stored and the player is left to shuffle. The case-insensitive
     * uniqueness of each column's words is a unique index on lower(), made by
     * the backfill migration once the rows exist.
     */
    public function up(): void
    {
        Schema::create('exercise_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->text('text');
            $table->boolean('is_correct')->default(false);

            $table->unique(['exercise_id', 'position']);
        });

        Schema::create('exercise_word_pairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->text('term');
            $table->text('translation');
            $table->unsignedSmallInteger('left_position')->nullable();
            $table->unsignedSmallInteger('right_position')->nullable();

            $table->unique(['exercise_id', 'position']);
            $table->unique(['exercise_id', 'left_position']);
            $table->unique(['exercise_id', 'right_position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercise_word_pairs');
        Schema::dropIfExists('exercise_options');
    }
};
