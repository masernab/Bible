import { Form, Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    reference as bibleReference,
    search as bibleSearch,
} from '@/routes/bible';

interface Verse {
    id: number;
    reference: string;
    verse: number;
    text: string;
}

interface Chapter {
    chapter: number;
    verses: Verse[];
}

interface Props {
    query: string;
    label: string | null;
    error: string | null;
    chapters: Chapter[];
}

const EXAMPLES = ['Josué 1:8', 'Juan 3 16', 'Salmos 23', '1 Corintios 13:4'];

export default function BibleReference({
    query,
    label,
    error,
    chapters,
}: Props) {
    return (
        <>
            <Head title="Look up by reference" />

            <div className="mx-auto max-w-2xl px-4 py-10">
                <header className="mb-8">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Look up by reference
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Type a citation like “Josué 1:8”, “Juan 3 16” or “Salmos
                        23” to read the passage. Book names are in Spanish
                        (Reina-Valera).
                    </p>
                    <Link
                        href={bibleSearch.url()}
                        className="mt-2 inline-block text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                    >
                        Searching by meaning? Try the semantic search →
                    </Link>
                </header>

                <Form
                    action={bibleReference.url()}
                    method="get"
                    className="mb-6"
                >
                    {({ processing }) => (
                        <div className="flex gap-2">
                            <Input
                                type="text"
                                name="q"
                                defaultValue={query}
                                autoFocus
                                placeholder="e.g. Josué 1:8"
                                className="flex-1"
                            />
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Searching…' : 'Search'}
                            </Button>
                        </div>
                    )}
                </Form>

                <div className="mb-8 flex flex-wrap items-center gap-2">
                    <span className="text-sm text-muted-foreground">
                        Examples:
                    </span>
                    {EXAMPLES.map((example) => (
                        <Link
                            key={example}
                            href={bibleReference.url({ query: { q: example } })}
                            className="rounded-full border px-3 py-1 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        >
                            {example}
                        </Link>
                    ))}
                </div>

                <Passage
                    query={query}
                    label={label}
                    error={error}
                    chapters={chapters}
                />
            </div>
        </>
    );
}

function Passage({ label, error, chapters }: Props) {
    if (error) {
        return <p className="text-sm text-muted-foreground">{error}</p>;
    }

    if (chapters.length === 0) {
        return null;
    }

    const showChapterHeadings = chapters.length > 1;

    return (
        <Card>
            <CardContent className="space-y-6 py-4">
                {label && (
                    <p className="text-sm font-medium text-muted-foreground">
                        {label}
                    </p>
                )}
                {chapters.map((group) => (
                    <div key={group.chapter} className="space-y-3">
                        {showChapterHeadings && (
                            <h2 className="text-sm font-semibold tracking-tight">
                                Chapter {group.chapter}
                            </h2>
                        )}
                        {group.verses.map((verse) => (
                            <p key={verse.id} className="leading-relaxed">
                                <sup className="mr-1 text-xs font-medium text-muted-foreground">
                                    {verse.verse}
                                </sup>
                                {verse.text}
                            </p>
                        ))}
                    </div>
                ))}
            </CardContent>
        </Card>
    );
}
