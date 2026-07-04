<?php

namespace App\Models;

use Database\Factories\VerseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Verse extends Model
{
    /** @use HasFactory<VerseFactory> */
    use HasFactory;

    protected $fillable = ['book_id', 'chapter', 'verse', 'reference', 'text', 'embedding'];

    /**
     * @return BelongsTo<Book, $this>
     */
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
