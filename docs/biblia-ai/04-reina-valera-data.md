# 04 · Reina-Valera data

> **Goal:** bring the 66 Bible JSON files into the project and understand their structure.

We'll use the [aruljohn/Reina-Valera](https://github.com/aruljohn/Reina-Valera) repository: one JSON
per book, plus a `Books.json` with the canonical list.

## Step 1 · Download the files

We'll place them in `database/data/reina-valera/`. The simplest way is to clone the repo into a
temp folder and copy the `.json` files:

```bash
git clone https://github.com/aruljohn/Reina-Valera.git /tmp/reina-valera
mkdir -p database/data/reina-valera
cp /tmp/reina-valera/*.json database/data/reina-valera/
```

On **PowerShell** (Windows):

```powershell
git clone https://github.com/aruljohn/Reina-Valera.git $env:TEMP\reina-valera
New-Item -ItemType Directory -Force database\data\reina-valera | Out-Null
Copy-Item "$env:TEMP\reina-valera\*.json" database\data\reina-valera\
```

You should end up with **66 book files** + `Books.json` inside `database/data/reina-valera/`.

> These files are **data**, not project dependencies. You can decide whether to commit them to git
> or add them to `.gitignore` (if the repo shouldn't carry heavy data).

## Step 2 · Understand the JSON structure

Each book (for example `Génesis.json`) has this shape:

```json
{
  "book": "Génesis",
  "chapters": [
    {
      "chapter": 1,
      "verses": [
        { "verse": 1, "text": "En el principio creó Dios los cielos y la tierra." },
        { "verse": 2, "text": "Y la tierra estaba desordenada y vacía, ..." }
      ]
    }
  ]
}
```

- `book` → the book name.
- `chapters[]` → each chapter has `chapter` (number) and `verses[]`.
- `verses[]` → each verse has `verse` (number) and `text` (the content).

`Books.json` holds the list of books in **canonical order**, useful to:
- Sort the books correctly.
- Classify each book as **Old Testament (39)** or **New Testament (27)**.

## Step 3 · Watch out for the names

This edition uses particular names worth knowing: `Los Actos` (Acts),
`Revelación` (Revelation), `San Mateo` (Matthew), `San Márcos` (Mark), `San Lúcas` (Luke),
`San Juan` (John), etc. We'll use `Books.json` as the **canonical source** of name and order so we
don't rely on guesses.

---

## How to verify

```bash
ls database/data/reina-valera | wc -l   # ~67 (66 books + Books.json)
```

Open `database/data/reina-valera/Génesis.json` and confirm it has the `book` and `chapters` keys.

## Checklist

- [ ] 66 book JSON files + `Books.json` in `database/data/reina-valera/`.
- [ ] You understand the `book → chapters[] → verses[]` structure.
- [ ] You decided whether to version this data in git or ignore it.

➡️ Next: [05 · Migrations & models](05-migrations-models.md)
