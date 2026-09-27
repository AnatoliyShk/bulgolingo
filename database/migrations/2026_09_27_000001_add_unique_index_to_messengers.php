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
     * A messenger account belongs to one user, but nothing stopped the same
     * (messenger_name, messenger_user_id) being linked twice. Each set of
     * duplicates collapses onto its oldest row, the link made first; nothing
     * else references messengers, so the later rows are simply deleted.
     */
    public function up(): void
    {
        $duplicates = DB::table('messengers')
            ->select('messenger_name', 'messenger_user_id')
            ->groupBy('messenger_name', 'messenger_user_id')
            ->havingRaw('count(*) > 1')
            ->get();

        foreach ($duplicates as $account) {
            $ids = DB::table('messengers')
                ->where('messenger_name', $account->messenger_name)
                ->where('messenger_user_id', $account->messenger_user_id)
                ->orderBy('id')
                ->pluck('id');

            DB::table('messengers')->whereIn('id', $ids->slice(1))->delete();
        }

        Schema::table('messengers', function (Blueprint $table) {
            $table->unique(['messenger_name', 'messenger_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messengers', function (Blueprint $table) {
            $table->dropUnique(['messenger_name', 'messenger_user_id']);
        });
    }
};
