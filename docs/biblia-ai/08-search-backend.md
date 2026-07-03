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
php artisan wayfinder:generate
```

This creates functions importable from `@/actions` or `@/routes` that you'll use in guide 09.

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

## Checklist

- [ ] `BibleSearch` returns results via `whereVectorSimilarTo`.
- [ ] Invokable controller rendering `bible/search`.
- [ ] `GET /bible` route named `bible.search`.
- [ ] `wayfinder:generate` run.

➡️ Next: [09 · React page](09-react-page.md)
