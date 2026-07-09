# 13 · Make search results clickable (open the passage)

> **Goal:** on `/bible` (the semantic search page), clicking a result verse should take the user to
> `/passage` (the reference page from [guide 12](12-reference-lookup.md)) showing that verse in
> context. No backend changes — we reuse the `q` query param the `/passage` route already accepts.

> 💡 This is an **optional extension** you can build after guide 12. It's a pure frontend change to
> one file: `resources/js/pages/bible/search.tsx`.

## Why this works with zero backend changes

Two facts we already have from the earlier guides:

1. Each search result carries a `reference` string like `"Josué 1:8"` (see the `Result` interface in
   `search.tsx` and the controller that renders it).
2. The `/passage` page (`bible.reference`) reads a citation from the `q` query param and resolves it —
   so `bibleReference.url({ query: { q: 'Josué 1:8' } })` is a valid deep link.

So making a result clickable is just: **wrap the card in a `<Link>` whose `href` is the passage URL for
that result's `reference`.**

---

## Step 1 · Import the passage route (already there)

Open `resources/js/pages/bible/search.tsx`. The import you need is **already present** at the top:

```tsx
import {
    reference as bibleReference,
    search as bibleSearch,
} from '@/routes/bible';
```

`bibleReference` is the Wayfinder helper for `/passage`. Nothing to add here.

## Step 2 · Wrap each result card in a link

Find the `Results` component near the bottom of the file. Today each result renders a plain `<Card>`:

```tsx
{results.map((result) => (
    <Card key={result.id}>
        <CardContent className="py-1">
            <div className="mb-1.5 flex items-center gap-2">
                <span className="text-sm font-medium text-muted-foreground">
                    {result.reference}
                </span>
                <Badge variant="secondary">{result.book}</Badge>
            </div>
            <p className="leading-relaxed">{result.text}</p>
        </CardContent>
    </Card>
))}
```

Wrap it in a `<Link>` (move the `key` onto the `Link`) and add a hover affordance so it reads as
clickable:

```tsx
{results.map((result) => (
    <Link
        key={result.id}
        href={bibleReference.url({ query: { q: result.reference } })}
        className="block rounded-xl transition-colors hover:bg-muted/50 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
    >
        <Card>
            <CardContent className="py-1">
                <div className="mb-1.5 flex items-center gap-2">
                    <span className="text-sm font-medium text-muted-foreground">
                        {result.reference}
                    </span>
                    <Badge variant="secondary">{result.book}</Badge>
                </div>
                <p className="leading-relaxed">{result.text}</p>
            </CardContent>
        </Card>
    </Link>
))}
```

`Link` is already imported from `@inertiajs/react` at the top of the file, so there's nothing else to
add. Inertia handles the navigation as a client-side visit (no full page reload).

> **Why `block` + `rounded-xl`?** `Card` is a block element with rounded corners; matching them on the
> `Link` keeps the hover/focus ring flush with the card instead of spilling into a rectangle.

## Step 3 · Build the frontend

```bash
npm run dev   # or: npm run build
```

---

## How to verify

- [ ] Search something on `/bible` (e.g. `amar al prójimo`) and get results.
- [ ] Hovering a result highlights the whole card (cursor + background change).
- [ ] Clicking a result navigates to `/passage?q=<that reference>` and shows the verse.
- [ ] The browser back button returns you to your search results.
- [ ] No console errors.

## Checklist

- [ ] Each result `<Card>` in `search.tsx` is wrapped in a `<Link>` to `bibleReference.url(...)`.
- [ ] `key` moved onto the `Link`; hover/focus styles added.
- [ ] `npm run dev` / `npm run build` run.

## Ideas to extend

- Highlight the **originating verse** on the passage page (pass `&highlight=<verse>` and style it).
- Show the **surrounding verses** by linking to the whole chapter instead of the single verse.

🎉 Back to the [index](README.md).
