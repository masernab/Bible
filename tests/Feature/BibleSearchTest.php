<?php

use App\Models\Book;
use App\Models\Verse;
use App\Services\BibleSearch;
use Illuminate\Database\Eloquent\Collection;

it('shows search results on the page', function () {
    $book = Book::factory()->create(['name' => 'Génesis']);
    $verse = Verse::factory()->for($book)->create([
        'reference' => 'Génesis 1:1',
        'text' => 'En el principio creó Dios los cielos y la tierra.',
    ]);

    // We don't depend on Postgres vector search or Ollama: mock the service.
    $this->mock(BibleSearch::class)
        ->shouldReceive('search')
        ->once()
        // The service returns an Eloquent collection, so the mock must too.
        ->andReturn(new Collection([$verse->load('book')]));

    $this->get('/bible?q=creación del mundo')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bible/search')
            ->where('query', 'creación del mundo')
            ->has('results', 1)
            ->where('results.0.reference', 'Génesis 1:1')
            ->where('results.0.book', 'Génesis')
        );
});

it('does not search with an empty query', function () {
    $this->mock(BibleSearch::class)->shouldNotReceive('search');

    $this->get('/bible')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bible/search')
            ->where('query', '')
            ->has('results', 0)
        );
});

it('validates the query length', function () {
    $this->get('/bible?q='.str_repeat('a', 201))
        ->assertSessionHasErrors('q');
});

it('exposes a result reference that resolves on the passage page', function () {
    // The clickable result links to /passage?q=<reference>. This proves that
    // contract end to end: the reference a result exposes must resolve there.
    $book = Book::factory()->create(['name' => 'Génesis', 'slug' => 'genesis']);
    $verse = Verse::factory()->for($book)->create([
        'chapter' => 1,
        'verse' => 1,
        'reference' => 'Génesis 1:1',
        'text' => 'En el principio creó Dios los cielos y la tierra.',
    ]);

    $this->mock(BibleSearch::class)
        ->shouldReceive('search')
        ->once()
        ->andReturn(new Collection([$verse->load('book')]));

    $reference = $this->get('/bible?q=creación del mundo')
        ->assertOk()
        ->viewData('page')['props']['results'][0]['reference'];

    // Following the link the search page builds must land on the verse.
    $this->get('/passage?q='.$reference)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bible/reference')
            ->where('label', 'Génesis 1:1')
            ->where('error', null)
            ->has('chapters.0.verses', 1)
            ->where('chapters.0.verses.0.text', 'En el principio creó Dios los cielos y la tierra.')
        );
});
