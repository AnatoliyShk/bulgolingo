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
        Schema::create('review_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lexema_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->decimal('stability_before', 12, 6)->nullable();
            $table->decimal('difficulty_before', 8, 6)->nullable();
            $table->decimal('stability_after', 12, 6);
            $table->decimal('difficulty_after', 8, 6);
            $table->unsignedInteger('elapsed_seconds');
            $table->unsignedInteger('scheduled_days');
            $table->string('scheduler');
            $table->timestamp('reviewed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_logs');
    }
};
