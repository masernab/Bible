# Bible — Semantic Search (Reina-Valera)

Search the Spanish **Reina-Valera** Bible by meaning, not just keywords. Type a phrase
like _"amar a tus enemigos"_ and get the closest verses ranked by semantic similarity.

The whole Bible (~31,000 verses) is turned into vector embeddings with a local
[Ollama](https://ollama.com) model and stored in PostgreSQL using
[`pgvector`](https://github.com/pgvector/pgvector). Queries run through an approximate
nearest-neighbour (HNSW) index, so search stays fast.

## Tech stack

- **Backend:** Laravel 13, PHP 8.5
- **Frontend:** React 19 + Inertia v3, Tailwind CSS v4, Vite
- **AI / embeddings:** [Laravel AI](https://github.com/laravel/ai) with a local **Ollama** model (`nomic-embed-text`, 768 dimensions)
- **Database:** PostgreSQL with the `pgvector` extension — a `compose.yaml` is included that spins up Postgres with `pgvector` already built in, so you don't have to install the extension yourself

## Requirements

Make sure you have these installed before starting:

| Requirement | Version | Notes |
|---|---|---|
| PHP | 8.5+ | with the `pdo_pgsql` extension enabled |
| Composer | 2.x | |
| Node.js | 20+ | comes with npm |
| Docker | latest | easiest way to run the database — the included `compose.yaml` provides Postgres + `pgvector` |
| PostgreSQL | 14+ | only if you prefer running it yourself instead of Docker; needs the `pgvector` extension |
| Ollama | latest | runs the embedding model locally, free, no API key |

> [!IMPORTANT]
> This project **does not run on SQLite**, even though `.env.example` ships with the
> Laravel default. Vector columns, the HNSW index, and cosine-distance search are
> PostgreSQL + `pgvector` features. You must use PostgreSQL.

## Installation

### 1. Clone and install dependencies

```bash
git clone git@github.com:masernab/Bible.git
cd Bible

composer install
npm install
```

### 2. Create your environment file

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Start the database

The included `compose.yaml` runs PostgreSQL with `pgvector` already built in — no
manual extension install needed. Start it with:

```bash
docker compose up -d
```

Then point `.env` at it. **Replace the SQLite block** (`DB_CONNECTION=sqlite`) with the
values that match `compose.yaml`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=bible
DB_USERNAME=bible
DB_PASSWORD=secret
```

<details>
<summary>Prefer to run PostgreSQL yourself instead of Docker?</summary>

Create a `bible` database on your own server and set matching credentials in `.env`. The
`pgvector` extension is enabled automatically by the migrations
(`Schema::ensureVectorExtensionExists()`), but the extension binary must already be
installed and the DB user must be able to run `CREATE EXTENSION`:

```bash
# Debian/Ubuntu
sudo apt install postgresql-16-pgvector
# macOS (Homebrew)
brew install pgvector
```

</details>

### 4. Set up Ollama (embedding model)

Install Ollama, then pull the model used to generate verse embeddings:

```bash
ollama pull nomic-embed-text
```

Ollama must be running (`ollama serve`, or the desktop app) at
`http://localhost:11434` while you generate embeddings and while searching. No API key
is required. To use a different host, model, or dimensions, set these in `.env`:

```dotenv
OLLAMA_URL=http://localhost:11434
BIBLE_EMBEDDING_MODEL=nomic-embed-text
BIBLE_EMBEDDING_DIMENSIONS=768
```

> [!NOTE]
> Prefer not to install Ollama locally? You can run it in Docker too:
> `docker run -d -p 11434:11434 ollama/ollama` then
> `docker exec -it <container> ollama pull nomic-embed-text`. The database already comes
> from `compose.yaml` (step 3).

### 5. Run migrations

```bash
php artisan migrate
```

### 6. Load the Bible and generate embeddings

```bash
# Import the ~31k Reina-Valera verses from database/data/reina-valera
php artisan bible:import

# Generate an embedding for every verse via Ollama (this takes a while)
php artisan bible:embed
```

`bible:embed` is resumable — it only processes verses without an embedding. Use
`--fresh` to regenerate everything, or `--limit=N` to try a small batch first.

### 7. Run the app

For development (server + queue + Vite together):

```bash
composer run dev
```

Or build assets and use the built-in server:

```bash
npm run build
php artisan serve
```

## Try it

Open the app and visit:

- **`/bible`** — semantic search. Type a phrase in Spanish (the corpus is Spanish) and
  get the most similar verses.
- **`/passage`** — look up a specific verse by reference (e.g. _Juan 3:16_).

## Running tests

```bash
php artisan test
```

## Troubleshooting

- **`Unable to locate file in Vite manifest`** — run `npm run build` (or `composer run dev`).
- **Search returns nothing** — make sure both `bible:import` and `bible:embed` finished,
  and that Ollama is running. Check pending verses with `php artisan bible:embed` (it
  reports how many are left).
- **`type "vector" does not exist`** — the `pgvector` extension isn't installed on your
  PostgreSQL server. Install it (step 3) and re-run `php artisan migrate:fresh`.
- **Slow or empty embeddings** — confirm the model is pulled (`ollama list`) and
  `OLLAMA_URL` is reachable.
