<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Nothing stopped one image being attached to the same exercise twice, which
     * showed it twice in the player. Each set of duplicates collapses onto its
     * oldest row; nothing references exercise_image, so the later rows are
     * simply deleted.
     */
    public function up(): void
    {
        $duplicates = DB::table('exercise_image')
            ->select('exercise_id', 'image_id')
            ->groupBy('exercise_id', 'image_id')
            ->havingRaw('count(*) > 1')
            ->get();

        foreach ($duplicates as $pair) {
            $ids = DB::table('exercise_image')
                ->where('exercise_id', $pair->exercise_id)
                ->where('image_id', $pair->image_id)
                ->orderBy('id')
                ->pluck('id');

            DB::table('exercise_image')->whereIn('id', $ids->slice(1))->delete();
        }

        Schema::table('exercise_image', function (Blueprint $table) {
            $table->unique(['exercise_id', 'image_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exercise_image', function (Blueprint $table) {
            $table->dropUnique(['exercise_id', 'image_id']);
        });
    }
};
