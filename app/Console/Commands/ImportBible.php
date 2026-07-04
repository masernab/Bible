<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\Verse;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('bible:import {--path=database/data/reina-valera}')]
#[Description('Import the Reina-Valera books and verses from the JSON files')]
class ImportBible extends Command
{
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

            $position = $order[$data['book']]['position'] ?? 99;

            $book = Book::updateOrCreate(
                ['slug' => Str::slug($data['book'])],
                [
                    'name' => $data['book'],
                    'testament' => $position <= 39 ? 'old' : 'new',
                    'position' => $position,
                ],
            );

            $rows = [];

            foreach ($data['chapters'] as $chapter) {
                foreach ($chapter['verses'] as $verse) {
                    $rows[] = [
                        'book_id' => $book->id,
                        'chapter' => $chapter['chapter'],
                        'verse' => $verse['verse'],
                        'reference' => "{$data['book']} {$chapter['chapter']}:{$verse['verse']}",
                        'text' => $verse['text'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // idempotent upsert on (book_id, chapter, verse)
            foreach (array_chunk($rows, 500) as $chunk) {
                Verse::upsert($chunk, ['book_id', 'chapter', 'verse'], ['reference', 'text', 'updated_at']);
            }

            $this->line("✔ {$data['book']} (".count($rows).' verses)');
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

        // Books.json is a plain list of book names in canonical order.
        foreach (array_values($books) as $i => $entry) {
            $name = is_array($entry) ? ($entry['book'] ?? $entry['name'] ?? null) : $entry;

            if ($name !== null) {
                $order[$name] = ['position' => $i + 1];
            }
        }

        return $order;
    }
}
