<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Verse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Verse>
 */
class VerseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $chapter = 1;
        $verse = fake()->numberBetween(1, 30);

        return [
            'book_id' => Book::factory(),
            'chapter' => $chapter,
            'verse' => $verse,
            'reference' => "Génesis {$chapter}:{$verse}",
            'text' => fake()->sentence(),
            'embedding' => null,
        ];
    }
}
