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
        Schema::table('learning_paths', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });

        Schema::create('learning_path_user', function (Blueprint $table) {
            $table->foreignId('learning_path_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['learning_path_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_path_user');

        Schema::table('learning_paths', function (Blueprint $table) {
            $table->foreignId('user_id')->after('id')->constrained()->cascadeOnDelete();
        });
    }
};
