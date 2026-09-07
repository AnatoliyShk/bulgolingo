<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * latest_exercise_at shipped one migration ahead of streak_counter, so
     * anyone who practised in between has a stamp with a counter still sitting
     * at its default of nothing — which the profile draws as a lit flame over a
     * zero, claiming a streak of no days while saying today is part of it.
     * A recorded practice is at least one day, so that is what they get.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNotNull('latest_exercise_at')
            ->where('streak_counter', '<', 1)
            ->update(['streak_counter' => 1]);
    }

    /**
     * Reverse the migrations.
     *
     * Nothing to undo: the rows this corrected are indistinguishable from ones
     * that legitimately reached a one-day streak, and resetting them to zero
     * would reintroduce the state it exists to remove.
     */
    public function down(): void
    {
        //
    }
};
