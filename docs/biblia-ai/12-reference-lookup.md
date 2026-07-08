# 12 · Look up a verse by reference (bonus)

> **Goal:** a second page at `/passage` that resolves a plain citation — `josue 1 8`, `Josué 1:8`,
> `Juan 3 16` or a whole chapter like `Salmos 23` — into the verse text. No embeddings and no Ollama:
> this is a straight database lookup that complements the semantic search from guides 08–09.

> 💡 This is an **optional extension** you can build after finishing guide 11. It reuses the same
> `books` / `verses` tables, so nothing new is imported.

## What we're building

| Input the user types | What we show |
|----------------------|--------------|
| `Josué 1:8` or `josue 1 8` | that single verse |
| `Juan 3 16` | John 3:16 |
| `Salmos 23` (no verse) | the whole chapter |
| `1 Corintios 13:4` | book names that start with a number work too |

The tricky part is parsing: a book name can itself **start with a number** (`1 Corintios`), so we can't
just split on the first digit. We match the chapter and verse **from the end** of the string.

---

## Step 1 · The lookup service

```bash
php artisan make:class Services/VerseReferenceLookup
```

In `app/Services/VerseReferenceLookup.php`:

```php
<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Verse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class VerseReferenceLookup
{
    /**
     * Resolve a human-typed reference such as "josue 1 8" or "Josué 1:8" into verses.
     * When only a chapter is given ("juan 3") the whole chapter is returned.
     *
     * @return array{
     *     book: Book|null, chapter: int|null, verse: int|null,
     *     label: string|null, verses: Collection<int, Verse>, error: string|null,
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

        $label = $verse !== null ? "{$book->name} {$chapter}:{$verse}" : "{$book->name} {$chapter}";

        $verses = Verse::query()
            ->where('book_id', $book->id)
            ->where('chapter', $chapter)
            ->when($verse !== null, fn ($query) => $query->where('verse', $verse))
            ->orderBy('verse')
            ->get();

        if ($verses->isEmpty()) {
            return $this->result(
                book: $book, chapter: $chapter, verse: $verse, label: $label,
                error: "We couldn't find \"{$label}\" in the text.",
            );
        }

        return $this->result(book: $book, chapter: $chapter, verse: $verse, label: $label, verses: $verses);
    }

    /**
     * Split into [book name, chapter, verse|null]. Because the book name may
     * start with a number ("1 Corintios"), match chapter/verse from the END.
     *
     * @return array{0: string, 1: int, 2: int|null}|null
     */
    private function parse(string $reference): ?array
    {
        $normalized = trim((string) preg_replace('/\s+/', ' ', $reference));

        if ($normalized === '') {
            return null;
        }

        // book ── chapter ── optional verse (separated by ":", "." or a space)
        if (! preg_match('/^(.+?)\s+(\d+)(?:[:.\s]+(\d+))?$/u', $normalized, $matches)) {
            return null;
        }

        $verse = isset($matches[3]) && $matches[3] !== '' ? (int) $matches[3] : null;

        return [$matches[1], (int) $matches[2], $verse];
    }

    /**
     * Match a typed book name to a Book, tolerating accents and abbreviations.
     * "josue" → Josué, "Josué" → Josué, "1 corintios" → 1 Corintios, "gen" → Génesis.
     */
    private function matchBook(string $name): ?Book
    {
        $slug = Str::slug($name); // strips accents, lowercases, spaces → hyphens

        if ($slug === '') {
            return null;
        }

        return Book::query()->where('slug', $slug)->first()
            ?? Book::query()->where('slug', 'like', $slug.'%')->orderBy('position')->first();
    }

    /**
     * @param  Collection<int, Verse>|null  $verses
     * @return array{
     *     book: Book|null, chapter: int|null, verse: int|null,
     *     label: string|null, verses: Collection<int, Verse>, error: string|null,
     * }
     */
    private function result(
        ?Book $book = null, ?int $chapter = null, ?int $verse = null,
        ?string $label = null, ?Collection $verses = null, ?string $error = null,
    ): array {
        return [
            'book' => $book, 'chapter' => $chapter, 'verse' => $verse,
            'label' => $label, 'verses' => $verses ?? new Collection, 'error' => $error,
        ];
    }
}
```

> **Why `Str::slug`?** In guide 06 the importer stored each book's `slug` with `Str::slug($name)`,
> so `Josué` → `josue` and `1 Corintios` → `1-corintios`. Slugging the user's input the same way
> makes the match accent- and case-insensitive for free. The `like $slug.'%'` fallback also accepts
> prefixes like `gen` → Génesis.

## Step 2 · The controller

```bash
php artisan make:controller VerseReferenceController --invokable
```

In `app/Http/Controllers/VerseReferenceController.php`:

```php
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

        $result = $query === '' ? null : $this->lookup->lookup($query);

        return Inertia::render('bible/reference', [
            'query'  => $query,
            'label'  => $result['label'] ?? null,
            'error'  => $result['error'] ?? null,
            'verses' => collect($result['verses'] ?? [])->map(fn ($verse) => [
                'id'        => $verse->id,
                'reference' => $verse->reference,
                'verse'     => $verse->verse,
                'text'      => $verse->text,
            ])->values(),
        ]);
    }
}
```

## Step 3 · The route

In `routes/web.php`, next to the existing `/bible` route:

```php
use App\Http\Controllers\VerseReferenceController;

Route::get('/passage', VerseReferenceController::class)->name('bible.reference');
```

Regenerate the typed route (see the `--with-form` warning in [guide 08](08-search-backend.md)) and format:

```bash
php artisan wayfinder:generate --with-form
vendor/bin/pint --dirty
```

## Step 4 · The React page

Create `resources/js/pages/bible/reference.tsx` (matches `Inertia::render('bible/reference', ...)`).
It mirrors the search page but renders a passage — a number superscript per verse, no similarity badge:

```tsx
import { Form, Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { reference as bibleReference, search as bibleSearch } from '@/routes/bible';

interface Verse {
    id: number;
    reference: string;
    verse: number;
    text: string;
}

interface Props {
    query: string;
    label: string | null;
    error: string | null;
    verses: Verse[];
}

const EXAMPLES = ['Josué 1:8', 'Juan 3 16', 'Salmos 23', '1 Corintios 13:4'];

export default function BibleReference({ query, label, error, verses }: Props) {
    return (
        <>
            <Head title="Look up by reference" />

            <div className="mx-auto max-w-2xl px-4 py-10">
                <header className="mb-8">
                    <h1 className="text-2xl font-semibold tracking-tight">Look up by reference</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Type a citation like “Josué 1:8”, “Juan 3 16” or “Salmos 23”. Book names are
                        in Spanish (Reina-Valera).
                    </p>
                    <Link
                        href={bibleSearch.url()}
                        className="mt-2 inline-block text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                    >
                        Searching by meaning? Try the semantic search →
                    </Link>
                </header>

                <Form action={bibleReference.url()} method="get" className="mb-6">
                    {({ processing }) => (
                        <div className="flex gap-2">
                            <Input
                                type="text"
                                name="q"
                                defaultValue={query}
                                autoFocus
                                placeholder="e.g. Josué 1:8"
                                className="flex-1"
                            />
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Searching…' : 'Search'}
                            </Button>
                        </div>
                    )}
                </Form>

                <div className="mb-8 flex flex-wrap items-center gap-2">
                    <span className="text-sm text-muted-foreground">Examples:</span>
                    {EXAMPLES.map((example) => (
                        <Link
                            key={example}
                            href={bibleReference.url({ query: { q: example } })}
                            className="rounded-full border px-3 py-1 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        >
                            {example}
                        </Link>
                    ))}
                </div>

                {error && <p className="text-sm text-muted-foreground">{error}</p>}

                {verses.length > 0 && (
                    <Card>
                        <CardContent className="space-y-3 py-1">
                            {label && (
                                <p className="text-sm font-medium text-muted-foreground">{label}</p>
                            )}
                            {verses.map((verse) => (
                                <p key={verse.id} className="leading-relaxed">
                                    <sup className="mr-1 text-xs font-medium text-muted-foreground">
                                        {verse.verse}
                                    </sup>
                                    {verse.text}
                                </p>
                            ))}
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}
```

Add a reciprocal link from the search page (`resources/js/pages/bible/search.tsx`) so the two pages
find each other:

```tsx
import { reference as bibleReference } from '@/routes/bible';
// …in the header:
<Link href={bibleReference.url()} className="…">Know the citation? Look it up by reference →</Link>
```

Build the frontend: `npm run dev` (or `npm run build`).

## Step 5 · Tests

```bash
php artisan make:test --pest VerseReferenceTest
```

Cover the parser's interesting cases — space vs. colon, whole-chapter, number-prefixed books, and the
two error paths:

```php
<?php

use App\Models\Book;
use App\Models\Verse;

function makeVerse(Book $book, int $chapter, int $verse, string $text = 'Texto de ejemplo.'): Verse
{
    return Verse::factory()->for($book)->create([
        'chapter' => $chapter,
        'verse' => $verse,
        'reference' => "{$book->name} {$chapter}:{$verse}",
        'text' => $text,
    ]);
}

it('finds a single verse from "book chapter verse"', function () {
    $book = Book::factory()->create(['name' => 'Josué', 'slug' => 'josue']);
    makeVerse($book, 1, 8);
    makeVerse($book, 1, 9);

    $this->get('/passage?q=josue 1 8')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bible/reference')
            ->where('label', 'Josué 1:8')
            ->where('error', null)
            ->has('verses', 1)
            ->where('verses.0.verse', 8)
        );
});

it('returns the whole chapter when no verse is given', function () {
    $book = Book::factory()->create(['name' => 'Juan', 'slug' => 'juan']);
    makeVerse($book, 3, 16);
    makeVerse($book, 3, 17);
    makeVerse($book, 4, 1);

    $this->get('/passage?q=juan 3')
        ->assertInertia(fn ($page) => $page->where('label', 'Juan 3')->has('verses', 2));
});

it('handles books whose name starts with a number', function () {
    $book = Book::factory()->create(['name' => '1 Corintios', 'slug' => '1-corintios']);
    makeVerse($book, 13, 4);

    $this->get('/passage?q=1 corintios 13:4')
        ->assertInertia(fn ($page) => $page->where('label', '1 Corintios 13:4')->has('verses', 1));
});

it('reports an unparseable reference', function () {
    $this->get('/passage?q=hola mundo')
        ->assertInertia(fn ($page) => $page->has('error')->has('verses', 0));
});
```

Run them:

```bash
php artisan test --compact --filter=VerseReferenceTest
```

> The reference lookup doesn't touch Ollama or vector search, so — unlike the semantic search tests —
> you don't need to mock any service. Plain factories against the test database are enough.

---

## How to verify

- [ ] `/passage` loads with no console errors.
- [ ] `Josué 1:8`, `josue 1 8` and `josue 1:8` all return the same verse.
- [ ] `Salmos 23` returns the whole chapter.
- [ ] `1 Corintios 13:4` works (number-prefixed book).
- [ ] A nonsense input shows a friendly error instead of crashing.

## Checklist

- [ ] `VerseReferenceLookup` service parses references and queries verses.
- [ ] Invokable `VerseReferenceController` rendering `bible/reference`.
- [ ] `GET /passage` route named `bible.reference` (+ `wayfinder:generate --with-form`).
- [ ] `resources/js/pages/bible/reference.tsx` page created, cross-linked with search.
- [ ] `--filter=VerseReferenceTest` green.

## Ideas to extend

- Verse **ranges** (`Juan 3:16-18`): capture an optional end verse in `parse()` and `whereBetween`.
- **Book abbreviations** table (e.g. `jn` → Juan) for quicker typing.
- A **chapter navigator** (prev/next) once you're viewing a whole chapter.

🎉 Bonus complete! Back to the [index](README.md).
