# 14 · Add a breadcrumb to `/passage` (pick a book or chapter)

> **Goal:** when a passage is shown on `/passage`, display a **breadcrumb** with two dropdowns —
> one to jump to **any book**, one to jump to **any chapter** of the current book. Selecting an
> option navigates using the same `q` deep-link the page already understands (from
> [guide 12](12-reference-lookup.md)), so there are no new routes.

> 💡 This builds on guide 12. It touches **three files**: the controller (to feed the dropdowns the
> book/chapter data), the React page, and the existing test. The UI components (`breadcrumb.tsx`,
> `dropdown-menu.tsx`) already ship with the app — nothing to install.

## What the breadcrumb looks like

```
Juan  ▾   /   Chapter 3  ▾
└ all 66 books   └ chapters 1…N of Juan
```

- Choosing a **book** goes to its **chapter 1** (`?q=<Book> 1`).
- Choosing a **chapter** goes to that chapter (`?q=<Book> <N>`).
- The breadcrumb only appears when a passage actually resolved (there is a current book). On the
  empty page, an error, or an unparseable query, it stays hidden.

---

## Step 1 · Feed the dropdowns from the controller

The `/passage` page is rendered by `app/Http/Controllers/VerseReferenceController.php`. Today it
throws away the resolved `book`/`chapter` that `VerseReferenceLookup` returns. We need four new
props:

| Prop | Type | For |
|------|------|-----|
| `books` | `string[]` | The book dropdown — all 66 names, in canonical order. |
| `currentBook` | `string \| null` | The book label shown in the breadcrumb. |
| `currentChapter` | `number \| null` | The chapter label (null when a whole book is shown). |
| `chapterCount` | `number \| null` | How many chapters the current book has (the chapter dropdown range). |

Add the two model imports at the top of the controller:

```php
use App\Models\Book;
use App\Models\Verse;
```

Then update `__invoke` to pull the book out of the lookup result and pass the new props. The
existing `query`/`label`/`error`/`chapters` block stays exactly as it is — you're only **adding**
below it:

```php
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
```

> **Why `max('chapter')`?** Chapters are numbered `1…N` with no gaps, so the highest chapter number
> *is* the count. One cheap query, no extra column needed.

Run Pint after editing PHP:

```bash
vendor/bin/pint --dirty --format agent
```

## Step 2 · Read the new props on the page

Open `resources/js/pages/bible/reference.tsx` and extend the `Props` interface:

```tsx
interface Props {
    query: string;
    label: string | null;
    error: string | null;
    chapters: Chapter[];
    books: string[];
    currentBook: string | null;
    currentChapter: number | null;
    chapterCount: number | null;
}
```

## Step 3 · Add the imports the breadcrumb needs

Below the existing imports at the top of the file, add the breadcrumb, dropdown, and a chevron icon:

```tsx
import { ChevronDown } from 'lucide-react';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbList,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
```

`Link` and `bibleReference` are already imported (guide 12), so the dropdown items can deep-link
without anything new.

## Step 4 · Build the `PassageBreadcrumb` component

Add this component to the file (a good spot is just above the `Passage` function near the bottom):

```tsx
function PassageBreadcrumb({
    books,
    currentBook,
    currentChapter,
    chapterCount,
}: Pick<Props, 'books' | 'currentBook' | 'currentChapter' | 'chapterCount'>) {
    if (!currentBook) {
        return null;
    }

    const chapterNumbers = Array.from(
        { length: chapterCount ?? 0 },
        (_, index) => index + 1,
    );

    const triggerClasses =
        'inline-flex items-center gap-1 rounded-md px-2 py-1 transition-colors hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none';

    return (
        <Breadcrumb className="mb-4">
            <BreadcrumbList>
                <BreadcrumbItem>
                    <DropdownMenu>
                        <DropdownMenuTrigger className={triggerClasses}>
                            {currentBook}
                            <ChevronDown className="size-3.5" />
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            align="start"
                            className="max-h-72 overflow-y-auto"
                        >
                            {books.map((book) => (
                                <DropdownMenuItem key={book} asChild>
                                    <Link
                                        href={bibleReference.url({
                                            query: { q: `${book} 1` },
                                        })}
                                    >
                                        {book}
                                    </Link>
                                </DropdownMenuItem>
                            ))}
                        </DropdownMenuContent>
                    </DropdownMenu>
                </BreadcrumbItem>

                {chapterNumbers.length > 0 && (
                    <>
                        <BreadcrumbSeparator />
                        <BreadcrumbItem>
                            <DropdownMenu>
                                <DropdownMenuTrigger className={triggerClasses}>
                                    {currentChapter
                                        ? `Chapter ${currentChapter}`
                                        : 'Chapter'}
                                    <ChevronDown className="size-3.5" />
                                </DropdownMenuTrigger>
                                <DropdownMenuContent
                                    align="start"
                                    className="grid max-h-72 grid-cols-5 gap-1 overflow-y-auto"
                                >
                                    {chapterNumbers.map((number) => (
                                        <DropdownMenuItem
                                            key={number}
                                            asChild
                                            className="justify-center"
                                        >
                                            <Link
                                                href={bibleReference.url({
                                                    query: {
                                                        q: `${currentBook} ${number}`,
                                                    },
                                                })}
                                            >
                                                {number}
                                            </Link>
                                        </DropdownMenuItem>
                                    ))}
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </BreadcrumbItem>
                    </>
                )}
            </BreadcrumbList>
        </Breadcrumb>
    );
}
```

> **Why the `grid grid-cols-5`?** Psalms has 150 chapters — a single tall column is painful to
> scan. A 5-column grid keeps the menu compact and still scrolls (`max-h-72 overflow-y-auto`).

## Step 5 · Render it above the passage

In the main `BibleReference` component, pass the new props down and drop the breadcrumb in right
before `<Passage />`:

```tsx
export default function BibleReference({
    query,
    label,
    error,
    chapters,
    books,
    currentBook,
    currentChapter,
    chapterCount,
}: Props) {
    return (
        <>
            <Head title="Look up by reference" />

            <div className="mx-auto max-w-2xl px-4 py-10">
                {/* …header, form and examples stay unchanged… */}

                <PassageBreadcrumb
                    books={books}
                    currentBook={currentBook}
                    currentChapter={currentChapter}
                    chapterCount={chapterCount}
                />

                <Passage
                    query={query}
                    label={label}
                    error={error}
                    chapters={chapters}
                />
            </div>
        </>
    );
}
```

> Keep the existing `<header>`, `<Form>` and the examples block exactly where they are — you're only
> inserting the `<PassageBreadcrumb …/>` line between the examples and `<Passage />`.

### Narrow the `Passage` component's type

`Passage` still types its argument as the whole `Props`. Now that `Props` has four new **required**
fields, TypeScript complains that `<Passage />` is called without them. `Passage` only needs a
subset, so narrow it with `Pick` (same trick as `PassageBreadcrumb`):

```tsx
function Passage({
    label,
    error,
    chapters,
}: Pick<Props, 'query' | 'label' | 'error' | 'chapters'>) {
    // …body unchanged…
}
```

## Step 6 · Build the frontend

```bash
npm run dev   # or: npm run build
```

---

## Step 7 · Update the test

The controller now returns extra props, so extend one case in
`tests/Feature/VerseReferenceTest.php` to lock the behaviour in. Add these assertions inside the
existing **"returns the whole chapter when no verse is given"** test (the `juan 3` case):

```php
$this->get('/passage?q=juan 3')
    ->assertOk()
    ->assertInertia(fn ($page) => $page
        ->where('label', 'Juan 3')
        ->has('chapters', 1)
        ->where('chapters.0.chapter', 3)
        ->has('chapters.0.verses', 2)
        // breadcrumb props:
        ->where('currentBook', 'Juan')
        ->where('currentChapter', 3)
        ->where('chapterCount', 4)   // makeVerse added chapters 3 and 4 → max is 4
        ->has('books')
    );
```

> `chapterCount` is `4` because that test seeds a verse in chapter `4` (`makeVerse($book, 4, 1)`),
> and `max('chapter')` returns the highest one.

Add a small dedicated test for the empty page (no breadcrumb data):

```php
it('exposes no current book on the empty page', function () {
    $this->get('/passage')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentBook', null)
            ->where('currentChapter', null)
            ->where('chapterCount', null)
        );
});
```

Run just this file:

```bash
php artisan test --compact --filter=VerseReference
```

---

## How to verify (in the browser)

- [ ] Open `/passage?q=Juan 3`. A breadcrumb shows **Juan ▾ / Chapter 3 ▾** above the text.
- [ ] Click the **book** dropdown → pick `Salmos` → lands on `Salmos 1`, breadcrumb updates.
- [ ] Click the **chapter** dropdown → it lists `1…150` in a grid → pick `23` → shows Psalm 23.
- [ ] Open `/passage` with no query → **no breadcrumb** appears.
- [ ] Search a single verse (`Josué 1:8`) → breadcrumb reads **Josué ▾ / Chapter 1 ▾**; picking a
      chapter opens the whole chapter.
- [ ] No console errors.

## Checklist

- [ ] Controller passes `books`, `currentBook`, `currentChapter`, `chapterCount`; `Book`/`Verse`
      imported; `pint --dirty` run.
- [ ] `Props` interface extended and props threaded into `BibleReference`.
- [ ] `PassageBreadcrumb` renders two dropdowns and hides when there's no current book.
- [ ] `npm run dev` / `npm run build` run.
- [ ] `VerseReferenceTest` updated and green.

## Ideas to extend

- Add **Prev / Next chapter** arrows beside the breadcrumb (`?q=<Book> <chapter ± 1>`, clamped to
  `1…chapterCount`).
- Group the book dropdown by **testament** (`Old` / `New`) using the `testament` column.
- Mark the **current** book/chapter as active in each menu (e.g. a check icon).

🎉 Back to the [index](README.md).
