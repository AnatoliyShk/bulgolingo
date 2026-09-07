<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * users.type is filtered on equality wherever the load-test tooling picks out
     * its own rows — GenerateQueueLoad, LoadTestReport and TeardownLoadTestData
     * all scan for type = 'filler' over a users table those runs deliberately
     * inflate — so give those lookups an index instead of a sequential scan.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['type']);
        });
    }
};
