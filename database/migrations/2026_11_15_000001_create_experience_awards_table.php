<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per exercise a user has been given XP for. Unlike
     * user_exercise_completions, a lesson or path reset never deletes from it,
     * so restarting cannot earn the same exercise's XP twice. Every current
     * completion was already awarded, so those pairs are backfilled.
     */
    public function up(): void
    {
        Schema::create('experience_awards', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['user_id', 'exercise_id']);
        });

        DB::statement('insert into experience_awards (user_id, exercise_id, created_at)
            select user_id, exercise_id, created_at from user_exercise_completions');
    }

    public function down(): void
    {
        Schema::dropIfExists('experience_awards');
    }
};
