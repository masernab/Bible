<?php

namespace App\Http\Controllers;

use App\Services\BibleSearch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BibleSearchController extends Controller
{
    public function __construct(private BibleSearch $search) {}

    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
        ]);

        $query = $validated['q'] ?? '';

        $results = $query === ''
            ? collect()
            : $this->search->search($query);

        return Inertia::render('bible/search', [
            'query' => $query,
            'results' => $results->map(fn ($verse) => [
                'id' => $verse->id,
                'reference' => $verse->reference,
                'text' => $verse->text,
                'book' => $verse->book->name,
            ]),
        ]);
    }
}
