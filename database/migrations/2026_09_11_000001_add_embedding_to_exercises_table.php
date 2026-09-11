<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 768 dimensions match the embedding model's output size. The index is
     * HNSW with vector_cosine_ops, so it serves cosine-distance (<=>) nearest
     * neighbour queries; other distance operators fall back to a sequential
     * scan. The pgvector extension is created first since the column type
     * does not exist without it.
     */
    public function up(): void
    {
        Schema::ensureVectorExtensionExists();

        Schema::table('exercises', function (Blueprint $table) {
            $table->vector('embedding', dimensions: 768)->nullable();
            $table->vectorIndex('embedding');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Dropping the column drops its HNSW index with it. The extension is left
     * installed: other tables may come to depend on it, and dropping it is not
     * this migration's to undo.
     */
    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropColumn('embedding');
        });
    }
};
