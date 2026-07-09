<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Verse;
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

        $book = $result['book'] ?? null;

        return Inertia::render('bible/reference', [
            'query' => $query,
            'label' => $result['label'] ?? null,
            'error' => $result['error'] ?? null,
            'chapters' => collect($result['chapters'] ?? [])
                ->map(fn ($verses, $chapter) => [
                    'chapter' => (int) $chapter,
                    'verses' => $verses->map(fn ($verse) => [
                        'id' => $verse->id,
                        'reference' => $verse->reference,
                        'verse' => $verse->verse,
                        'text' => $verse->text,
                    ])->values(),
                ])
                ->values(),
            'books' => Book::query()->orderBy('position')->pluck('name'),
            'currentBook' => $book?->name,
            'currentChapter' => $result['chapter'] ?? null,
            'chapterCount' => $book
                ? (int) Verse::query()->where('book_id', $book->id)->max('chapter')
                : null,
        ]);
    }
}
