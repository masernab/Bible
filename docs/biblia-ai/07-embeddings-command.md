# 07 · Embeddings command (`bible:embed`)

> **Goal:** iterate over the verses that don't have an embedding yet, generate them in batches with
> Ollama and store them in the `vector` column. The command must be **resumable** (it only processes
> the missing ones).

> ⏱️ **Heads up:** there are ~31,000 verses. With local Ollama this takes a while (from minutes to
> over an hour depending on your machine). Since it's resumable, you can stop and continue later.
> Make sure **Ollama is running** (guide 02).

## Step 1 · Create the command

```bash
php artisan make:command EmbedBible
```

In `app/Console/Commands/EmbedBible.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\Verse;
use Illuminate\Console\Command;
use Laravel\Ai\Embeddings;

class EmbedBible extends Command
{
    protected $signature = 'bible:embed {--fresh : Regenerate all embeddings} {--limit=0 : Process at most N verses} {--chunk=100}';

    protected $description = 'Generate embeddings for the verses using the configured provider (Ollama)';

    public function handle(): int
    {
        if ($this->option('fresh')) {
            Verse::query()->update(['embedding' => null]);
            $this->warn('Embeddings reset (--fresh).');
        }

        $query = Verse::query()->whereNull('embedding');

        $total = (int) $this->option('limit') ?: $query->count();
        $chunk = (int) $this->option('chunk');

        if ($total === 0) {
            $this->info('No pending verses. All set. ✅');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();
        $processed = 0;

        // chunkById avoids skipping rows as we modify them while iterating.
        $query->orderBy('id')->chunkById($chunk, function ($verses) use ($bar, &$processed, $total) {
            $texts = $verses->pluck('text')->all();

            $response = Embeddings::for($texts)->generate();

            foreach ($verses as $i => $verse) {
                $verse->update(['embedding' => $response->embeddings[$i]]);
            }

            $processed += $verses->count();
            $bar->advance($verses->count());

            return $processed < $total; // stop once we hit --limit
        });

        $bar->finish();
        $this->newLine(2);
        $this->info('Embeddings generated. Pending: '.Verse::whereNull('embedding')->count());

        return self::SUCCESS;
    }
}
```

> **Why in batches?** `Embeddings::for([...])->generate()` sends several texts in a single call,
> much more efficient than one at a time.

Format:

```bash
vendor/bin/pint --dirty
```

## Step 2 · Try a subset first

Before processing everything, test with a few:

```bash
php artisan bible:embed --limit=200
```

Check in Tinker that the vector was stored:

```bash
php artisan tinker --execute '$v = App\Models\Verse::whereNotNull("embedding")->first(); echo $v->reference." -> ".count($v->embedding)." dims\n";'
```

It should print a reference and **768 dims**.

## Step 3 · Process the rest

```bash
php artisan bible:embed
```

You can stop it (Ctrl+C) and run it again; it resumes where it left off.

---

## Optional: create the HNSW index after loading

If in guide 05 you left the column **without** an index, now is the time to create it (it speeds up
search). Create a new migration and in `up()`:

```php
DB::statement('CREATE INDEX verses_embedding_hnsw ON verses USING hnsw (embedding vector_cosine_ops)');
```

## How to verify

```bash
php artisan tinker --execute 'echo App\Models\Verse::whereNull("embedding")->count()." pending\n";'
```

It should reach **0 pending** when done.

## Checklist

- [ ] `bible:embed` command created and resumable.
- [ ] `--limit=200` test stores 768-dim vectors.
- [ ] Every verse has an embedding (0 pending).
- [ ] (Optional) HNSW index created.

➡️ Next: [08 · Search (backend)](08-search-backend.md)
