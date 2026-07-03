# 06 · Import command (`bible:import`)

> **Goal:** a command that reads the 66 JSON files and fills the `books` and `verses` tables (still
> **without** embeddings). It must be *idempotent*: running it twice doesn't duplicate data.

## Step 1 · Create the command

```bash
php artisan make:command ImportBible
```

In `app/Console/Commands/ImportBible.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\Verse;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportBible extends Command
{
    protected $signature = 'bible:import {--path=database/data/reina-valera}';

    protected $description = 'Import the Reina-Valera books and verses from the JSON files';

    public function handle(): int
    {
        $path = base_path($this->option('path'));

        if (! is_dir($path)) {
            $this->error("Folder does not exist: {$path}");

            return self::FAILURE;
        }

        $order = $this->canonicalOrder($path);
        $files = glob($path.'/*.json');

        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);

            if ($name === 'Books') {
                continue; // not a book
            }

            $data = json_decode(file_get_contents($file), true);

            $book = Book::updateOrCreate(
                ['slug' => Str::slug($data['book'])],
                [
                    'name'      => $data['book'],
                    'testament' => ($order[$data['book']]['position'] ?? 99) <= 39 ? 'old' : 'new',
                    'position'  => $order[$data['book']]['position'] ?? 99,
                ],
            );

            $rows = [];

            foreach ($data['chapters'] as $chapter) {
                foreach ($chapter['verses'] as $verse) {
                    $rows[] = [
                        'book_id'   => $book->id,
                        'chapter'   => $chapter['chapter'],
                        'verse'     => $verse['verse'],
                        'reference' => "{$data['book']} {$chapter['chapter']}:{$verse['verse']}",
                        'text'      => $verse['text'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // idempotent upsert on (book_id, chapter, verse)
            foreach (array_chunk($rows, 500) as $chunk) {
                Verse::upsert($chunk, ['book_id', 'chapter', 'verse'], ['reference', 'text', 'updated_at']);
            }

            $this->line("✔ {$data['book']} (".count($rows)." verses)");
        }

        $this->info('Import complete: '.Verse::count().' verses, '.Book::count().' books.');

        return self::SUCCESS;
    }

    /**
     * Read Books.json to get each book's canonical order/position.
     *
     * @return array<string, array{position: int}>
     */
    private function canonicalOrder(string $path): array
    {
        $file = $path.'/Books.json';

        if (! is_file($file)) {
            return [];
        }

        $books = json_decode(file_get_contents($file), true);
        $order = [];

        // Adjust this to the real shape of Books.json (a list of names or of objects).
        foreach (array_values($books) as $i => $entry) {
            $name = is_array($entry) ? ($entry['book'] ?? $entry['name'] ?? null) : $entry;

            if ($name !== null) {
                $order[$name] = ['position' => $i + 1];
            }
        }

        return $order;
    }
}
```

> ⚠️ **Check `Books.json`** before trusting `canonicalOrder()`: it may be a plain list of strings or
> a list of objects. Open the file and adapt the reading so `position` (1..66) comes out right —
> that's where the OT/NT classification comes from (position ≤ 39 = Old Testament).

Format:

```bash
vendor/bin/pint --dirty
```

## Step 2 · Run it

```bash
php artisan bible:import
```

---

## How to verify

```bash
php artisan tinker --execute 'echo App\Models\Book::count()." books, ".App\Models\Verse::count()." verses\n";'
```

You should see **66 books** and **~31,000 verses**. Run the command again and confirm the numbers
**don't change** (idempotency).

## Checklist

- [ ] `bible:import` command created.
- [ ] `Books.json` read correctly (position and testament assigned right).
- [ ] 66 books and ~31,000 verses in the DB.
- [ ] Running it twice doesn't duplicate data.

➡️ Next: [07 · Embeddings command](07-embeddings-command.md)
