<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A topic a user has said they want to learn about, one row per topic per
     * user. The uuid is not nullable here, unlike the columns `uuid:backfill`
     * fills: the table is new, so every row has come through HasUuidV7 and
     * there are no pre-existing rows to leave blank. The embedding mirrors the
     * exercises and lexemas columns — 768 dimensions for the embedding model's
     * output, with an HNSW index under vector_cosine_ops so cosine-distance
     * (<=>) lookups use the index instead of scanning.
     */
    public function up(): void
    {
        Schema::ensureVectorExtensionExists();

        Schema::create('desired_topics', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('topic');
            $table->vector('embedding', dimensions: 768)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'topic']);
        });

        Schema::table('desired_topics', function (Blueprint $table) {
            $table->vectorIndex('embedding');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Dropping the table drops the embedding's HNSW index with it. The vector
     * extension stays installed: exercises and lexemas depend on it, and it is
     * not this migration's to undo.
     */
    public function down(): void
    {
        Schema::dropIfExists('desired_topics');
    }
};
