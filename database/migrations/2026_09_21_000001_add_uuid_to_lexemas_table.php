<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Same shape as the uuid column added to users, learning_paths, lessons,
     * exercises, scripted_dialogues, and scripted_lines: nullable and with no
     * DB-level default, since existing rows are filled by the `uuid:backfill`
     * command rather than by the migration rewriting the table, and new rows
     * get theirs from App\Models\Concerns\HasUuidV7 on create.
     */
    public function up(): void
    {
        Schema::table('lexemas', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lexemas', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
