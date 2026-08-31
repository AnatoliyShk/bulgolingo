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
        Schema::table('lexemas', function (Blueprint $table) {
            $table->foreignId('exercise_id')->nullable()->after('word')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lexemas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exercise_id');
        });
    }
};
