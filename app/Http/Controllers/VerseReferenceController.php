<?php

namespace App\Http\Controllers;

use App\Services\VerseReferenceLookup;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VerseReferenceController extends Controller
{
    public function __construct(private VerseReferenceLookup $lookup) {}

    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $query = trim($validated['q'] ?? '');

        $result = $query === ''
            ? null
            : $this->lookup->lookup($query);

        return Inertia::render('bible/reference', [
            'query' => $query,
            'label' => $result['label'] ?? null,
            'error' => $result['error'] ?? null,
            'verses' => collect($result['verses'] ?? [])->map(fn ($verse) => [
                'id' => $verse->id,
                'reference' => $verse->reference,
                'verse' => $verse->verse,
                'text' => $verse->text,
            ])->values(),
        ]);
    }
}
