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
     * The column is added nullable and backfilled from the old string `type`
     * column before being made required, the same sequencing the role_id
     * migration used: existing rows have no type_id to default to until the
     * string value has been looked up against the new reference table.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('type_id')->nullable()->after('role_id')->constrained();
        });

        foreach (DB::table('types')->pluck('id', 'name') as $name => $id) {
            DB::table('users')->where('type', $name)->update(['type_id' => $id]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('type_id')->nullable(false)->change();
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('type')->default('regular')->after('type_id');
        });

        foreach (DB::table('types')->pluck('name', 'id') as $id => $name) {
            DB::table('users')->where('type_id', $id)->update(['type' => $name]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->index('type');
            $table->dropConstrainedForeignId('type_id');
        });
    }
};
