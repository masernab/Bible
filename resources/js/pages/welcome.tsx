import { Form, Head, Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { dashboard } from '@/routes';
import { search as bibleSearch } from '@/routes/bible';

// The scripture text is Spanish (Reina-Valera), so the example queries are
// Spanish too — cross-language search would return irrelevant verses.
const EXAMPLES = [
    'el buen pastor',
    'no tengáis miedo',
    'la creación del mundo',
    'amar al prójimo',
    'el perdón de los pecados',
];

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Semantic Bible Search" />

            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="mx-auto flex w-full max-w-2xl items-center justify-end gap-4 px-6 py-6 text-sm">
                    {auth.user && (
                        <Link
                            href={dashboard()}
                            className="rounded-md border px-4 py-1.5 transition-colors hover:bg-muted"
                        >
                            Dashboard
                        </Link>
                    )}
                </header>

                <main className="mx-auto flex w-full max-w-2xl flex-1 flex-col justify-center px-6 pb-24">
                    <h1 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                        Semantic Bible search
                    </h1>
                    <p className="mt-3 text-base text-muted-foreground">
                        Find verses by what they mean — not just the words they
                        contain. The text is the Spanish Reina-Valera, so search
                        in Spanish.
                    </p>

                    <Form
                        action={bibleSearch.url()}
                        method="get"
                        className="mt-8"
                    >
                        {({ processing }) => (
                            <div className="flex gap-2">
                                <Input
                                    type="text"
                                    name="q"
                                    autoFocus
                                    placeholder="e.g. el buen pastor"
                                    className="flex-1"
                                />
                                <Button type="submit" disabled={processing}>
                                    Search
                                </Button>
                            </div>
                        )}
                    </Form>

                    <div className="mt-6 flex flex-wrap items-center gap-2">
                        <span className="text-sm text-muted-foreground">
                            Try:
                        </span>
                        {EXAMPLES.map((example) => (
                            <Link
                                key={example}
                                href={bibleSearch.url({
                                    query: { q: example },
                                })}
                                className="rounded-full border px-3 py-1 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                            >
                                {example}
                            </Link>
                        ))}
                    </div>
                </main>

                <footer className="mx-auto w-full max-w-2xl px-6 py-6 text-center text-xs text-muted-foreground">
                    Reina-Valera · Local embeddings with Ollama
                    (nomic-embed-text) · Vector search on PostgreSQL + pgvector
                </footer>
            </div>
        </>
    );
}
