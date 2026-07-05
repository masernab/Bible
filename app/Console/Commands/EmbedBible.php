<?php

namespace App\Console\Commands;

use App\Models\Verse;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Enums\Lab;

#[Signature('bible:embed {--fresh : Regenerate all embeddings} {--limit=0 : Process at most N verses} {--chunk=100}')]
#[Description('Generate embeddings for the verses using the configured provider (Ollama)')]
class EmbedBible extends Command
{
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

        $model = config('bible.embedding.model');
        $dimensions = (int) config('bible.embedding.dimensions');

        $bar = $this->output->createProgressBar($total);
        $bar->start();
        $processed = 0;

        // chunkById avoids skipping rows as we modify them while iterating.
        $query->orderBy('id')->chunkById($chunk, function (Collection $verses) use ($bar, &$processed, $total, $model, $dimensions) {
            $texts = $verses->pluck('text')->all();

            $response = Embeddings::for($texts)
                ->dimensions($dimensions)
                ->generate(Lab::Ollama, $model);

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
