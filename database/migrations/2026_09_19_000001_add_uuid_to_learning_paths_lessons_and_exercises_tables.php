<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nullable and with no DB-level default on purpose: existing rows are
     * meant to be filled by the `uuid:backfill` command rather than by the
     * migration rewriting the whole table, and new rows get theirs from
     * App\Models\Concerns\HasUuidV7 on create.
     */
    public function up(): void
    {
        foreach (['learning_paths', 'lessons', 'exercises'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['learning_paths', 'lessons', 'exercises'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn('uuid');
            });
        }
    }
};
