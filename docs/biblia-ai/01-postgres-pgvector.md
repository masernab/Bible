# 01 · PostgreSQL + pgvector

> **Goal:** get a PostgreSQL database with the `pgvector` extension, and make the Laravel app use it
> instead of SQLite.

Laravel's vector search (`whereVectorSimilarTo`, the `vector` column, the HNSW index) **only works
on PostgreSQL with the `pgvector` extension**. SQLite is not an option here.

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed and running.

> Why Docker? On Windows it's the simplest, most reliable way to get Postgres **with `pgvector`
> already included**. The official `pgvector/pgvector` image ships the extension ready to use.

---

## Step 1 · Define the Postgres service

Create (or edit) a `compose.yaml` file in the project root:

```yaml
services:
  pgsql:
    image: pgvector/pgvector:pg17
    ports:
      - '5432:5432'
    environment:
      POSTGRES_DB: bible
      POSTGRES_USER: bible
      POSTGRES_PASSWORD: secret
    volumes:
      - pgsql-data:/var/lib/postgresql/data

volumes:
  pgsql-data:
```

Start it:

```bash
docker compose up -d
```

## Step 2 · Point Laravel at Postgres

In your `.env`, switch the connection to `pgsql` with the same credentials:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=bible
DB_USERNAME=bible
DB_PASSWORD=secret
```

> The `pgsql` connection is already defined in `config/database.php`; you don't need to edit that file.

Clear the cached config just in case:

```bash
php artisan config:clear
```

## Step 3 · Confirm pgvector is available

Open a SQL console inside the container and create the extension (Laravel also creates it in the
migration via `Schema::ensureVectorExtensionExists()`, but let's test it now):

```bash
docker compose exec pgsql psql -U bible -d bible -c "CREATE EXTENSION IF NOT EXISTS vector;"
docker compose exec pgsql psql -U bible -d bible -c "\dx"
```

You should see `vector` in the list of extensions.

---

## How to verify

```bash
php artisan db:show
```

It must show **Connection: pgsql** and the `bible` database with no errors.

## Checklist

- [ ] Docker running and `docker compose up -d` started the `pgsql` service.
- [ ] `.env` set to `DB_CONNECTION=pgsql` with correct credentials.
- [ ] `php artisan db:show` connects to Postgres.
- [ ] The `vector` extension appears in `\dx`.

➡️ Next: [02 · Ollama](02-ollama.md)
