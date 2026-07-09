<?php

use App\Models\Book;
use App\Models\Verse;

function makeVerse(Book $book, int $chapter, int $verse, string $text = 'Texto de ejemplo.'): Verse
{
    return Verse::factory()->for($book)->create([
        'chapter' => $chapter,
        'verse' => $verse,
        'reference' => "{$book->name} {$chapter}:{$verse}",
        'text' => $text,
    ]);
}

it('finds a single verse from "book chapter verse"', function () {
    $book = Book::factory()->create(['name' => 'Josué', 'slug' => 'josue']);
    makeVerse($book, 1, 8, 'Nunca se apartará de tu boca este libro de la ley.');
    makeVerse($book, 1, 9, 'Mira que te mando que te esfuerces y seas valiente.');

    $this->get('/passage?q=josue 1 8')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bible/reference')
            ->where('label', 'Josué 1:8')
            ->where('error', null)
            ->has('chapters', 1)
            ->where('chapters.0.chapter', 1)
            ->has('chapters.0.verses', 1)
            ->where('chapters.0.verses.0.verse', 8)
            ->where('chapters.0.verses.0.reference', 'Josué 1:8')
        );
});

it('accepts the colon format and ignores accents', function () {
    $book = Book::factory()->create(['name' => 'Josué', 'slug' => 'josue']);
    makeVerse($book, 1, 8);

    $this->get('/passage?q=Josué 1:8')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('label', 'Josué 1:8')
            ->has('chapters.0.verses', 1)
        );
});

it('returns the whole chapter when no verse is given', function () {
    $book = Book::factory()->create(['name' => 'Juan', 'slug' => 'juan']);
    makeVerse($book, 3, 16);
    makeVerse($book, 3, 17);
    makeVerse($book, 4, 1);

    $this->get('/passage?q=juan 3')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('label', 'Juan 3')
            ->has('chapters', 1)
            ->where('chapters.0.chapter', 3)
            ->has('chapters.0.verses', 2)
            ->where('currentBook', 'Juan')
            ->where('currentChapter', 3)
            ->where('chapterCount', 4)
            ->has('books')
        );
});

it('returns the whole book when only a book name is given', function () {
    $book = Book::factory()->create(['name' => 'Juan', 'slug' => 'juan']);
    makeVerse($book, 1, 1);
    makeVerse($book, 3, 16);
    makeVerse($book, 3, 17);

    $this->get('/passage?q=juan')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('label', 'Juan')
            ->where('error', null)
            ->has('chapters', 2)
            ->where('chapters.0.chapter', 1)
            ->has('chapters.0.verses', 1)
            ->where('chapters.0.verses.0.reference', 'Juan 1:1')
            ->where('chapters.1.chapter', 3)
            ->has('chapters.1.verses', 2)
        );
});

it('handles books whose name starts with a number', function () {
    $book = Book::factory()->create(['name' => '1 Corintios', 'slug' => '1-corintios']);
    makeVerse($book, 13, 4, 'El amor es sufrido, es benigno.');

    $this->get('/passage?q=1 corintios 13:4')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('label', '1 Corintios 13:4')
            ->has('chapters.0.verses', 1)
            ->where('chapters.0.verses.0.verse', 4)
        );
});

it('reports an unknown book', function () {
    $this->get('/passage?q=Nolibro 1:1')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('error')
            ->has('chapters', 0)
        );
});

it('reports an unparseable reference', function () {
    $this->get('/passage?q=hola mundo')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('error')
            ->has('chapters', 0)
        );
});

it('shows nothing for an empty query', function () {
    $this->get('/passage')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bible/reference')
            ->where('query', '')
            ->where('error', null)
            ->has('chapters', 0)
        );
});

it('exposes no current book on the empty page', function () {
    $this->get('/passage')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentBook', null)
            ->where('currentChapter', null)
            ->where('chapterCount', null)
        );
});

it('validates the query length', function () {
    $this->get('/passage?q='.str_repeat('a', 101))
        ->assertSessionHasErrors('q');
});
