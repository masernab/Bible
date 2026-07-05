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
     *
     * The HNSW index is approximate. pgvector's default hnsw.ef_search (40)
     * gives poor recall on this dataset and misses the true nearest verses,
     * so we raise it at the database level: every new session inherits it
     * automatically, keeping the search service free of per-query tuning.
     */
    public function up(): void
    {
        DB::statement('CREATE INDEX verses_embedding_hnsw ON verses USING hnsw (embedding vector_cosine_ops)');

        $database = DB::getDatabaseName();
        $efSearch = (int) config('bible.search.ef_search');

        DB::statement("ALTER DATABASE \"{$database}\" SET hnsw.ef_search = {$efSearch}");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $database = DB::getDatabaseName();

        DB::statement("ALTER DATABASE \"{$database}\" RESET hnsw.ef_search");
        DB::statement('DROP INDEX IF EXISTS verses_embedding_hnsw');
    }
};
