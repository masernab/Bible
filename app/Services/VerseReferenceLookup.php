<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Verse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;

class VerseReferenceLookup
{
    /**
     * Resolve a human-typed reference such as "josue 1 8" or "Josué 1:8" into verses.
     *
     * When only a chapter is given ("juan 3") the whole chapter is returned, and
     * when only a book is given ("juan") the whole book is returned.
     *
     * @return array{
     *     book: Book|null,
     *     chapter: int|null,
     *     verse: int|null,
     *     label: string|null,
     *     verses: Collection<int, Verse>,
     *     chapters: SupportCollection<int, Collection<int, Verse>>,
     *     error: string|null,
     * }
     */
    public function lookup(string $reference): array
    {
        $parsed = $this->parse($reference);

        if ($parsed === null) {
            return $this->result(
                error: 'We couldn\'t read that reference. Try something like "Josué 1:8" or "Juan 3 16".',
            );
        }

        [$bookName, $chapter, $verse] = $parsed;

        $book = $this->matchBook($bookName);

        if ($book === null) {
            return $this->result(error: "We couldn't find the book \"{$bookName}\".");
        }

        $label = match (true) {
            $verse !== null => "{$book->name} {$chapter}:{$verse}",
            $chapter !== null => "{$book->name} {$chapter}",
            default => $book->name,
        };

        $verses = Verse::query()
            ->where('book_id', $book->id)
            ->when($chapter !== null, fn ($query) => $query->where('chapter', $chapter))
            ->when($verse !== null, fn ($query) => $query->where('verse', $verse))
            ->orderBy('chapter')
            ->orderBy('verse')
            ->get();

        if ($verses->isEmpty()) {
            return $this->result(
                book: $book,
                chapter: $chapter,
                verse: $verse,
                label: $label,
                error: "We couldn't find \"{$label}\" in the text.",
            );
        }

        return $this->result(
            book: $book,
            chapter: $chapter,
            verse: $verse,
            label: $label,
            verses: $verses,
        );
    }

    /**
     * Split a reference into [book name, chapter|null, verse|null].
     *
     * The book name may itself start with a number ("1 Corintios"), so the
     * chapter and verse are matched from the end of the string. When no trailing
     * chapter is present the whole reference is treated as a book name.
     *
     * @return array{0: string, 1: int|null, 2: int|null}|null
     */
    private function parse(string $reference): ?array
    {
        $normalized = trim((string) preg_replace('/\s+/', ' ', $reference));

        if ($normalized === '') {
            return null;
        }

        if (preg_match('/^(.+?)\s+(\d+)(?:[:.\s]+(\d+))?$/u', $normalized, $matches)) {
            $verse = isset($matches[3]) ? (int) $matches[3] : null;

            return [$matches[1], (int) $matches[2], $verse];
        }

        return [$normalized, null, null];
    }

    /**
     * Match a typed book name to a Book, tolerating accents and abbreviations.
     */
    private function matchBook(string $name): ?Book
    {
        $slug = Str::slug($name);

        if ($slug === '') {
            return null;
        }

        return Book::query()->where('slug', $slug)->first()
            ?? Book::query()
                ->where('slug', 'like', $slug.'%')
                ->orderBy('position')
                ->first();
    }

    /**
     * @param  Collection<int, Verse>|null  $verses
     * @return array{
     *     book: Book|null,
     *     chapter: int|null,
     *     verse: int|null,
     *     label: string|null,
     *     verses: Collection<int, Verse>,
     *     chapters: SupportCollection<int, Collection<int, Verse>>,
     *     error: string|null,
     * }
     */
    private function result(
        ?Book $book = null,
        ?int $chapter = null,
        ?int $verse = null,
        ?string $label = null,
        ?Collection $verses = null,
        ?string $error = null,
    ): array {
        $verses ??= new Collection;

        return [
            'book' => $book,
            'chapter' => $chapter,
            'verse' => $verse,
            'label' => $label,
            'verses' => $verses,
            'chapters' => $verses->groupBy('chapter'),
            'error' => $error,
        ];
    }
}
