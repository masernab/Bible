# 08 · Semantic search (backend)

> **Goal:** a service and a controller that take a text query and return the most similar verses by
> meaning, using `whereVectorSimilarTo`.

## Step 1 · Search service

```bash
php artisan make:class Services/BibleSearch
```

In `app/Services/BibleSearch.php`:

```php
<?php

namespace App\Services;

use App\Models\Verse;
use Illuminate\Support\Collection;

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
```

> `whereVectorSimilarTo` compares by **cosine similarity**, filters out anything below
> `minSimilarity` (0.0–1.0) and orders from most to least similar. Raise `minSimilarity` for stricter
> results, lower it for more results.

## Step 2 · Controller

```bash
php artisan make:controller BibleSearchController --invokable
```

In `app/Http/Controllers/BibleSearchController.php`:

```php
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
            'query'   => $query,
            'results' => $results->map(fn ($verse) => [
                'id'        => $verse->id,
                'reference' => $verse->reference,
                'text'      => $verse->text,
                'book'      => $verse->book->name,
            ]),
        ]);
    }
}
```

## Step 3 · Route

In `routes/web.php`:

```php
use App\Http\Controllers\BibleSearchController;

Route::get('/bible', BibleSearchController::class)->name('bible.search');
```

## Step 4 · Generate the typed route (Wayfinder)

So the frontend can call this route with types, generate the Wayfinder definitions:

```bash
php artisan wayfinder:generate --with-form
```

This creates functions importable from `@/actions` or `@/routes` that you'll use in guide 09.

> ⚠️ **Use `--with-form`.** `wayfinder:generate` regenerates **all** your route files, not just the
> new one. The Laravel starter kit's pages (login, register, profile…) call `SomeRoute.form()`, and
> the Vite plugin is configured with `formVariants: true` (see `vite.config.ts`). If you run
> `wayfinder:generate` **without** `--with-form`, it strips the `.form()` helpers and breaks
> `npm run types:check` across those existing pages. Either pass `--with-form` here, or just let
> `npm run dev` / `npm run build` regenerate them (the Vite plugin already uses the right options).

Format the PHP:

```bash
vendor/bin/pint --dirty
```

---

## How to verify

With embeddings already generated (guide 07) and Ollama running, quick test in Tinker:

```bash
php artisan tinker --execute '$r = app(App\Services\BibleSearch::class)->search("love your neighbor", 5); $r->each(fn($v) => print($v->reference." - ".mb_substr($v->text,0,60)."\n"));'
```

Coherent verses on the topic should show up, even if they don't contain those exact words.

> The corpus is Spanish (Reina-Valera), so Spanish queries (`"amar al prójimo"`) work best.
>
> If the results look irrelevant even though embeddings exist, the culprit is almost always the HNSW
> index's `hnsw.ef_search` still sitting at the pgvector default of 40 — see the warning in
> [guide 07](07-embeddings-command.md#create-the-hnsw-index-after-loading-recommended). Raise it to 100.

## Checklist

- [ ] `BibleSearch` returns results via `whereVectorSimilarTo`.
- [ ] Invokable controller rendering `bible/search`.
- [ ] `GET /bible` route named `bible.search`.
- [ ] `wayfinder:generate --with-form` run (and `npm run types:check` still passes).

➡️ Next: [09 · React page](09-react-page.md)
