# 10 · Tests (Pest)

> **Goal:** automated tests that make sure the import and the search work.

> 💡 In Claude Code, activate the **`pest-testing`** skill when writing these tests.

## Important note about the test database

`whereVectorSimilarTo` **only works on PostgreSQL + pgvector**. You have two paths:

1. **Search test against a real Postgres** (more faithful): configure `.env.testing` to point at a
   Postgres+pgvector test database (it can be another DB in the same container from guide 01).
2. **Isolate the service** in the controller test (mocking `BibleSearch`) so you don't depend on
   the vector DB. Useful to test the HTTP/Inertia flow without Postgres.

Below is an example of each type.

---

## Test A · Import (no vectors needed)

```bash
php artisan make:test BibleImportTest
```

`tests/Feature/BibleImportTest.php`:

```php
<?php

use App\Models\Book;
use App\Models\Verse;

it('imports books and verses from the JSON files', function () {
    // Use a small fixtures folder so the test is fast.
    $this->artisan('bible:import', ['--path' => 'tests/fixtures/reina-valera'])
        ->assertSuccessful();

    expect(Book::count())->toBeGreaterThan(0)
        ->and(Verse::where('reference', 'Génesis 1:1')->exists())->toBeTrue();
});

it('is idempotent', function () {
    $this->artisan('bible:import', ['--path' => 'tests/fixtures/reina-valera']);
    $count = Verse::count();

    $this->artisan('bible:import', ['--path' => 'tests/fixtures/reina-valera']);

    expect(Verse::count())->toBe($count);
});
```

> Create `tests/fixtures/reina-valera/` with **1 or 2 small books** (e.g. a `Génesis.json` trimmed
> to one chapter) and a minimal `Books.json`, so you don't load all 66 on every test.

## Test B · Search controller (mocking the service)

```bash
php artisan make:test BibleSearchTest
```

`tests/Feature/BibleSearchTest.php`:

```php
<?php

use App\Models\Book;
use App\Models\Verse;
use App\Services\BibleSearch;

it('shows search results on the page', function () {
    $book = Book::factory()->create(['name' => 'Génesis']);
    $verse = Verse::factory()->for($book)->create([
        'reference' => 'Génesis 1:1',
        'text'      => 'En el principio creó Dios los cielos y la tierra.',
    ]);

    // We don't depend on Postgres/Ollama: we mock the service.
    $this->mock(BibleSearch::class)
        ->shouldReceive('search')
        ->andReturn(collect([$verse->load('book')]));

    $this->get('/bible?q=creation')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bible/search')
            ->where('query', 'creation')
            ->has('results', 1)
        );
});

it('does not search with an empty query', function () {
    $this->get('/bible')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('results', 0));
});
```

## Test C (optional) · Real vector search

If you set up `.env.testing` with Postgres+pgvector and have Ollama running, you can write a test
that seeds verses, runs `bible:embed` on a few and checks that `BibleSearch::search('...')` returns
the expected verse. Mark it as an integration test (it can be slow) and keep it separate from the
fast suite.

---

## Run

```bash
php artisan test --compact --filter=Bible
```

## How to verify

- [ ] The import and controller tests pass green.

## Checklist

- [ ] Small fixtures in `tests/fixtures/reina-valera/`.
- [ ] `BibleImportTest` covers import and idempotency.
- [ ] `BibleSearchTest` covers the page (with the service mocked).
- [ ] `php artisan test --filter=Bible` passes.

➡️ Next: [11 · Final verification](11-final-verification.md)
