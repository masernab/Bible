<?php

namespace App\Services;

use App\Models\Verse;
use Illuminate\Database\Eloquent\Collection;

class BibleSearch
{
    /**
     * Search for verses semantically similar to the query.
     *
     * @return Collection<int, Verse>
     */
    public function search(string $query, int $limit = 20, float $minSimilarity = 0.4): Collection
    {
        return Verse::query()
            ->with('book')
            // Passing the string lets the AI SDK generate the query embedding automatically.
            ->whereVectorSimilarTo('embedding', $query, minSimilarity: $minSimilarity)
            ->limit($limit)
            ->get();
    }
}
