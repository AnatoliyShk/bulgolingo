<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * review_logs is the largest table in the schema and, being append-only, has
     * carried no index but its primary key. What that costs today is deletion:
     * user_id is a cascading foreign key, Postgres does not index a referencing
     * column on its own, so every account removal — ProfileController::destroy()
     * and the admin UserController — sequentially scans the whole log.
     *
     * user_id alone, and nothing wider: nothing in the app reads this table yet,
     * so columns added for a hypothetical history query would be paid for on
     * every graded review — the hottest write path there is — and read back by
     * no one. Widen it when a reader actually lands.
     */
    public function up(): void
    {
        Schema::table('review_logs', function (Blueprint $table) {
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('review_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });
    }
};
