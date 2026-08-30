<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_lexema', function (Blueprint $table) {
            $table->unsignedTinyInteger('state')->default(0); // new|learning|review|relearning
            $table->unsignedInteger('interval_days')->default(0);
            $table->timestamp('due_at')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->unsignedSmallInteger('lapses')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_lexema', function (Blueprint $table) {
            $table->dropColumn(['state', 'interval_days', 'due_at', 'last_reviewed_at', 'lapses']);
        });
    }
};
