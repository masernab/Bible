# Semantic Bible Search (Reina-Valera) with the Laravel AI SDK

Step-by-step guide to build, on top of this **Laravel 13 + React (Inertia)** app, a
**semantic / vector search** over the Bible using the [Laravel AI SDK](https://laravel.com/docs/13.x/ai-sdk).

Instead of matching exact words, we search by **meaning**: you type
"love your neighbor" and relevant verses show up even if they don't contain those exact words.

---

## How does it work? (the big picture)

1. We **import** the Bible (66 books) from JSON files into the database.
2. For each **verse** we generate an *embedding*: a vector of numbers that represents its meaning.
   We do this with a local AI model (Ollama).
3. We store that vector in a special `vector` column in **PostgreSQL** (the `pgvector` extension).
4. When searching, we turn your query into another vector and ask the database for the **closest**
   verses (cosine similarity) using `whereVectorSimilarTo`.
5. We show the results in a **React page**.

```
Reina-Valera JSON ──▶ `verses` table ──▶ embeddings (Ollama) ──▶ vector(768) column
                                                                       │
      user query ──▶ embedding ──▶ whereVectorSimilarTo ──────────────▶ results ──▶ React page
```

---

## Decisions already made

| Topic | Decision | Why |
|-------|----------|-----|
| Database | **PostgreSQL + `pgvector`** | The only DB with native `whereVectorSimilarTo` support. |
| Embeddings | **Local Ollama**, model `nomic-embed-text` (**768 dims**) | Free and local, no API key. |
| Granularity | **Per verse** (~31,000) | Precise, verse-level results. |
| v1 scope | Backend + React search page | A complete, usable flow. |

> ⚠️ **Important about dimensions**: `nomic-embed-text` produces **768**-dimension vectors. If you
> ever switch models (e.g. `mxbai-embed-large` = 1024, or OpenAI `text-embedding-3-small` = 1536)
> you'll have to **change the `vector` column and regenerate every embedding**.

---

## Roadmap (follow the guides in order)

| # | Guide | What you achieve |
|---|-------|------------------|
| 01 | [Postgres + pgvector](01-postgres-pgvector.md) | Database ready with vector support |
| 02 | [Ollama](02-ollama.md) | Embedding model running on your machine |
| 03 | [Install the AI SDK](03-install-ai-sdk.md) | `laravel/ai` configured with Ollama |
| 04 | [Reina-Valera data](04-reina-valera-data.md) | The 66 JSON files inside the project |
| 05 | [Migrations & models](05-migrations-models.md) | `books`/`verses` tables + models |
| 06 | [Import command](06-import-command.md) | `php artisan bible:import` |
| 07 | [Embeddings command](07-embeddings-command.md) | `php artisan bible:embed` |
| 08 | [Search (backend)](08-search-backend.md) | Service, controller and route |
| 09 | [React page](09-react-page.md) | Search UI at `/bible` |
| 10 | [Tests](10-tests.md) | Pest tests that keep everything working |
| 11 | [Final verification](11-final-verification.md) | End-to-end test of the whole flow |
| 12 | [Look up by reference](12-reference-lookup.md) *(bonus)* | A `/passage` page to read a verse by citation (`Josué 1:8`) |
| 13 | [Clickable results](13-clickable-results.md) *(bonus)* | Clicking a search result on `/bible` opens it in `/passage` |
| 14 | [Passage breadcrumb](14-passage-breadcrumb.md) *(bonus)* | A breadcrumb on `/passage` to jump to any book or chapter |

---

## Before you start you'll need

- **Docker Desktop** (for Postgres + pgvector) — guide 01.
- **Ollama** installed — guide 02.
- This project already cloned, with `composer install` and `npm install` done.

## Project conventions (respect them in the code)

- Generate files with `php artisan make:...` instead of creating them by hand.
- In PHP: explicit return types and constructor property promotion.
- After editing PHP run `vendor/bin/pint --dirty`.
- Before touching a package, check its docs (in Claude Code: the `search-docs` tool).

When you finish a guide, tick its checklist and move on to the next. Let's go! 🚀
