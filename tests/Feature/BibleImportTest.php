<?php

use App\Models\Book;
use App\Models\Verse;

it('imports books and verses from the JSON files', function () {
    $this->artisan('bible:import', ['--path' => 'tests/fixtures/reina-valera'])
        ->assertSuccessful();

    expect(Book::count())->toBe(2)
        ->and(Verse::where('reference', 'Génesis 1:1')->exists())->toBeTrue()
        ->and(Verse::where('reference', 'San Juan 1:1')->exists())->toBeTrue();
});

it('classifies the testament from the canonical order', function () {
    $this->artisan('bible:import', ['--path' => 'tests/fixtures/reina-valera']);

    expect(Book::where('name', 'Génesis')->value('testament'))->toBe('old')
        ->and(Book::where('name', 'San Juan')->value('testament'))->toBe('new');
});

it('is idempotent', function () {
    $this->artisan('bible:import', ['--path' => 'tests/fixtures/reina-valera']);
    $count = Verse::count();

    $this->artisan('bible:import', ['--path' => 'tests/fixtures/reina-valera']);

    expect(Verse::count())->toBe($count);
});
