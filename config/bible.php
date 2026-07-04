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

];
