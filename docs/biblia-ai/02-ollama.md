# 02 · Ollama (local embedding model)

> **Goal:** get Ollama running with the `nomic-embed-text` model, which turns text into **768-dimension**
> vectors with no cost and no API key.

## Step 1 · Install Ollama

Download and install from [ollama.com/download](https://ollama.com/download) (there's a Windows
installer). When it finishes, Ollama runs as a service and exposes a local API at
`http://localhost:11434`.

Verify it responds:

```bash
curl http://localhost:11434/api/tags
```

## Step 2 · Pull the embedding model

```bash
ollama pull nomic-embed-text
```

This model is designed for *embeddings* (not chat) and produces vectors of **768** numbers.

## Step 3 · Test that it generates embeddings

```bash
curl http://localhost:11434/api/embeddings -d "{\"model\":\"nomic-embed-text\",\"prompt\":\"In the beginning God created the heavens and the earth\"}"
```

It should return JSON with an `embedding` key containing a long list of numbers.

> 💡 To count how many numbers it returns (should be 768), in PowerShell:
> ```powershell
> (Invoke-RestMethod -Uri http://localhost:11434/api/embeddings -Method Post -Body '{"model":"nomic-embed-text","prompt":"hello"}').embedding.Count
> ```

---

## Notes

- **Keep it running**: Ollama must be up whenever you run `bible:embed` (guide 07) or perform
  searches (guide 08), because both the verses and each query are turned into vectors using this model.
- If you later want higher quality you can try `mxbai-embed-large` (1024 dims), but remember that
  **switching models forces you to re-create the `vector` column and regenerate every embedding**.

## How to verify

- [ ] `curl http://localhost:11434/api/tags` lists `nomic-embed-text`.
- [ ] The `/api/embeddings` test returns a vector with 768 numbers.

## Checklist

- [ ] Ollama installed and running.
- [ ] `nomic-embed-text` model pulled.
- [ ] Embedding test working.

➡️ Next: [03 · Install the AI SDK](03-install-ai-sdk.md)
