<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * When the user last finished an exercise, which is the whole of what the
     * profile's streak flame needs: lit if that moment falls on today, cold
     * otherwise. Nullable because every existing account has never practised,
     * and a default of now() would credit them all with a session they never
     * had.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dateTime('latest_exercise_at')->nullable()->after('experience');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('latest_exercise_at');
        });
    }
};
