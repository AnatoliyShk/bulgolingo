<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Mirrors the exercises embedding column: 768 dimensions to match the
     * embedding model's output size, and an HNSW index with vector_cosine_ops
     * so cosine-distance (<=>) nearest neighbour queries are served by the
     * index — other distance operators fall back to a sequential scan. The
     * pgvector extension is ensured first since the column type does not
     * exist without it.
     */
    public function up(): void
    {
        Schema::ensureVectorExtensionExists();

        Schema::table('lexemas', function (Blueprint $table) {
            $table->vector('embedding', dimensions: 768)->nullable();
            $table->vectorIndex('embedding');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Dropping the column drops its HNSW index with it. The extension is left
     * installed: other tables depend on it, and dropping it is not this
     * migration's to undo.
     */
    public function down(): void
    {
        Schema::table('lexemas', function (Blueprint $table) {
            $table->dropColumn('embedding');
        });
    }
};
