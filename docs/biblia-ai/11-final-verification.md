# 11 · Final verification (end-to-end)

> **Goal:** walk the whole flow from start to finish and confirm the semantic search works.

## Active prerequisites

- [ ] Postgres container running (`docker compose up -d`).
- [ ] Ollama running with `nomic-embed-text`.

## Full walkthrough

```bash
# 1. Migrate (creates the extension, tables and index)
php artisan migrate

# 2. Import the Bible (66 books, ~31,000 verses)
php artisan bible:import

# 3. Generate embeddings for a subset to test quickly
php artisan bible:embed --limit=500

# 4. Start the app
composer run dev
```

Open `/bible` in the browser and try **meaning-based** searches, for example:

- "love your neighbor"
- "create the heavens and the earth"
- "the good shepherd"
- "do not be afraid"

You should get thematically related verses, even if they don't contain those exact words.

## Once it all works, process the rest

```bash
php artisan bible:embed        # complete the ~31,000 embeddings
```

(If you deferred the HNSW index, create it now — see guide 07.)

## Automated tests

```bash
php artisan test --compact --filter=Bible
```

---

## Troubleshooting

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| `whereVectorSimilarTo` throws a SQL error | The DB is not Postgres or `pgvector` is missing | Review guide 01; `\dx` must list `vector`. |
| Embeddings stay `null` or `bible:embed` fails | Ollama isn't running or the model is missing | Review guide 02 (`ollama pull nomic-embed-text`). |
| `count($embedding)` ≠ 768 | Different model than expected | Use `nomic-embed-text`, or adjust `dimensions:` in the migration and re-embed. |
| Search returns nothing | `minSimilarity` too high or embeddings missing | Lower `minSimilarity` (guide 08) and confirm 0 pending (guide 07). |
| `/bible` page blank / Vite error | Frontend not built | `npm run dev` or `npm run build`. |
| "Unable to locate file in Vite manifest" error | Missing build | `npm run build`. |

## Final project checklist

- [ ] Postgres + pgvector operational.
- [ ] Ollama + `nomic-embed-text` operational.
- [ ] `laravel/ai` installed and configured.
- [ ] Data imported (66 books, ~31,000 verses).
- [ ] Embeddings generated (0 pending).
- [ ] `/bible` page returns coherent, meaning-based results.
- [ ] `--filter=Bible` tests green.

🎉 Project complete! Head back to the [index](README.md) to review any stage.

➡️ Optional next: [12 · Look up by reference](12-reference-lookup.md) — add a `/passage` page to read a verse by citation.
