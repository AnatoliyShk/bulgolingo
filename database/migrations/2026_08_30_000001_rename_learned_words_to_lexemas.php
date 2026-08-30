<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('learned_words', 'lexemas');
        Schema::rename('user_learned_word', 'user_lexema');

        Schema::table('user_lexema', function (Blueprint $table) {
            $table->renameColumn('learned_word_id', 'lexema_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_lexema', function (Blueprint $table) {
            $table->renameColumn('lexema_id', 'learned_word_id');
        });

        Schema::rename('user_lexema', 'user_learned_word');
        Schema::rename('lexemas', 'learned_words');
    }
};
