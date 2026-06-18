<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_path_user', function (Blueprint $table) {
            $table->boolean('is_completed')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('learning_path_user', function (Blueprint $table) {
            $table->dropColumn('is_completed');
        });
    }
};
