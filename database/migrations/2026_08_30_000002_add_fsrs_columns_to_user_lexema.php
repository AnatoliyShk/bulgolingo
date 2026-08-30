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
            $table->decimal('stability', 12, 6)->nullable();
            $table->decimal('difficulty', 8, 6)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_lexema', function (Blueprint $table) {
            $table->dropColumn(['stability', 'difficulty']);
        });
    }
};
