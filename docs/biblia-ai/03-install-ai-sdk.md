# 03 · Install and configure the Laravel AI SDK

> **Goal:** install `laravel/ai`, configure it to use **Ollama** as the embeddings provider and
> confirm that `Str::of('...')->toEmbeddings()` returns 768 numbers.

## Step 1 · Install the package

```bash
composer require laravel/ai
```

## Step 2 · Publish the configuration

Publish the config file so you can adjust it (the tag name may vary by version; if the first one
doesn't exist, try the second):

```bash
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"
# or, if the SDK exposes it by tag:
php artisan vendor:publish --tag=ai-config
```

This creates `config/ai.php`. Open it and look at the **providers** and **embeddings** sections.

> 📌 Before editing, check the [AI SDK docs](https://laravel.com/docs/13.x/ai-sdk#embeddings)
> (or in Claude Code, the `search-docs` tool with queries like *"embeddings provider ollama config"*)
> to confirm the exact key names for your version. Below is the usual shape.

## Step 3 · Configure Ollama as the embeddings provider

Add to `.env`:

```dotenv
# Embeddings provider
AI_EMBEDDINGS_PROVIDER=ollama
OLLAMA_URL=http://localhost:11434
OLLAMA_EMBEDDING_MODEL=nomic-embed-text
```

And in `config/ai.php` make sure that:

- The **ollama** provider points at `env('OLLAMA_URL')`.
- The embeddings *default* uses the `ollama` provider and the `nomic-embed-text` model.

> The exact names (`AI_EMBEDDINGS_PROVIDER`, etc.) depend on how the published `config/ai.php` is
> written. Adjust the `.env` keys so they match what that file reads.

Clear the config:

```bash
php artisan config:clear
```

## Step 4 · Test embeddings from Tinker

```bash
php artisan tinker
```

Inside Tinker:

```php
$e = Illuminate\Support\Str::of('In the beginning God created the heavens and the earth')->toEmbeddings();
count($e); // should be 768
```

You can also test batch mode:

```php
$r = Laravel\Ai\Embeddings::for(['God is love', 'The Lord is my shepherd'])->generate();
count($r->embeddings);      // 2
count($r->embeddings[0]);   // 768
```

---

## How to verify

- [ ] `count($e)` in Tinker returns **768**.
- [ ] No connection errors (if any: make sure Ollama is running — guide 02).

## Checklist

- [ ] `laravel/ai` installed.
- [ ] `config/ai.php` published and using Ollama as the embeddings provider.
- [ ] `.env` has the Ollama keys.
- [ ] Tinker test returns 768 dimensions.

➡️ Next: [04 · Reina-Valera data](04-reina-valera-data.md)
