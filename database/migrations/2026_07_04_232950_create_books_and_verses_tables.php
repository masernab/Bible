<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
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
            // Dimensions are fixed at 768 here to keep this migration an
            // immutable record; the runtime source of truth is
            // config('bible.embedding.dimensions'), which must match.
            // No HNSW index yet: created in a later migration after the ~31k
            // embeddings are loaded, so the bulk insert stays fast.
            $table->vector('embedding', dimensions: 768)->nullable();
            $table->timestamps();

            $table->unique(['book_id', 'chapter', 'verse']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verses');
        Schema::dropIfExists('books');
    }
};
