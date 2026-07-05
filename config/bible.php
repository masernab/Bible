<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Embedding model
    |--------------------------------------------------------------------------
    |
    | The model used to turn verse text into vectors, and the number of
    | dimensions it produces. These two values must stay in sync: changing the
    | model to one with a different output size requires re-generating every
    | embedding and altering the `verses.embedding` vector column to match.
    |
    */

    'embedding' => [
        'model' => env('BIBLE_EMBEDDING_MODEL', 'nomic-embed-text'),
        'dimensions' => (int) env('BIBLE_EMBEDDING_DIMENSIONS', 768),
    ],

    /*
    |--------------------------------------------------------------------------
    | Vector search tuning
    |--------------------------------------------------------------------------
    |
    | The HNSW index is approximate: `hnsw.ef_search` controls how many
    | candidates it inspects per query. The pgvector default (40) gives poor
    | recall on this dataset and misses the true closest verses, so we raise
    | it. It should comfortably exceed the search limit; 100 keeps queries at
    | a few milliseconds while returning the exact nearest neighbours.
    |
    */

    'search' => [
        'ef_search' => (int) env('BIBLE_HNSW_EF_SEARCH', 100),
    ],

];
