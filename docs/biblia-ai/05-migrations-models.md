# 05 · Migrations & models

> **Goal:** create the `books` and `verses` tables (with a `vector(768)` column and an HNSW index)
> and their Eloquent models.

## Step 1 · Create the migration

```bash
php artisan make:migration create_books_and_verses_tables
```

Edit the generated file in `database/migrations/`. Suggested content:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make sure the pgvector extension exists before creating vector columns.
        Schema::ensureVectorExtensionExists();

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('name');            // e.g. "Génesis"
            $table->string('slug')->unique();  // e.g. "genesis"
            $table->string('testament');       // "old" | "new"
            $table->unsignedSmallInteger('position'); // canonical order 1..66
            $table->timestamps();
        });

        Schema::create('verses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('chapter');
            $table->unsignedSmallInteger('verse');
            $table->string('reference');       // e.g. "Génesis 1:1"
            $table->text('text');
            $table->vector('embedding', dimensions: 768)->nullable();
            $table->timestamps();

            $table->unique(['book_id', 'chapter', 'verse']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verses');
        Schema::dropIfExists('books');
    }
};
```

> **About the HNSW index:** it makes vector search much faster, but slows down the bulk insertion of
> embeddings. You have two options:
> 1. Add `->index()` to the `embedding` column now (simplest).
> 2. Leave it **without an index** during the load (guide 07) and create it afterwards in a separate
>    migration, once the ~31,000 embeddings are stored. Recommended for the first load.

Run it:

```bash
php artisan migrate
```

## Step 2 · Create the models and their factories

```bash
php artisan make:model Book --factory
php artisan make:model Verse --factory
```

`app/Models/Book.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    protected $fillable = ['name', 'slug', 'testament', 'position'];

    public function verses(): HasMany
    {
        return $this->hasMany(Verse::class);
    }
}
```

`app/Models/Verse.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Verse extends Model
{
    protected $fillable = ['book_id', 'chapter', 'verse', 'reference', 'text', 'embedding'];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'embedding' => 'array', // automatically converts between a PHP array and the vector
        ];
    }
}
```

> The `'embedding' => 'array'` cast lets you assign/read the vector as a PHP array while Laravel
> handles the conversion to Postgres's `vector` format.

## Step 3 · (Optional) Factory for tests

Edit `database/factories/VerseFactory.php` to generate test verses (you'll use this in guide 10).
Minimal example:

```php
public function definition(): array
{
    return [
        'book_id'   => Book::factory(),
        'chapter'   => 1,
        'verse'     => fake()->numberBetween(1, 30),
        'reference' => 'Génesis 1:1',
        'text'      => fake()->sentence(),
        'embedding' => null,
    ];
}
```

After editing PHP, format:

```bash
vendor/bin/pint --dirty
```

---

## How to verify

```bash
php artisan migrate:status
php artisan db:table verses   # shows the columns, including `embedding` of type vector
```

## Checklist

- [ ] Migration created with `ensureVectorExtensionExists()` and the `books`/`verses` tables.
- [ ] `embedding` column of type `vector(768)`.
- [ ] `php artisan migrate` with no errors.
- [ ] `Book` and `Verse` models with the relation and the `embedding => array` cast.

➡️ Next: [06 · Import command](06-import-command.md)
