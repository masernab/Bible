<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Created after the ~31k embeddings are loaded so the bulk insert stays
     * fast. HNSW with cosine distance matches the similarity search used by
     * the AI SDK's whereVectorSimilarTo() helper.
     */
    public function up(): void
    {
        DB::statement('CREATE INDEX verses_embedding_hnsw ON verses USING hnsw (embedding vector_cosine_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS verses_embedding_hnsw');
    }
};
