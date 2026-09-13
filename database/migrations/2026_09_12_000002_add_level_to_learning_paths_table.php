<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Holds a LanguageLevel value, A1 to C2. Nullable because the paths that
     * already exist have no level yet, and guessing one for them would put a
     * wrong level in front of students until an admin corrected it.
     */
    public function up(): void
    {
        Schema::table('learning_paths', function (Blueprint $table) {
            $table->string('level', 2)->nullable()->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_paths', function (Blueprint $table) {
            $table->dropColumn('level');
        });
    }
};
