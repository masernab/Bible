import { Form, Head, Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import {
    reference as bibleReference,
    search as bibleSearch,
} from '@/routes/bible';

interface Result {
    id: number;
    reference: string;
    text: string;
    book: string;
}

interface Props {
    query: string;
    results: Result[];
}

export default function BibleSearch({ query, results }: Props) {
    return (
        <>
            <Head title="Semantic Bible search" />

            <div className="mx-auto max-w-2xl px-4 py-10">
                <header className="mb-8">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Semantic Bible search
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Search by meaning, not exact words. The text is the
                        Spanish Reina-Valera, so search in Spanish.
                    </p>
                    <Link
                        href={bibleReference.url()}
                        className="mt-2 inline-block text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                    >
                        Know the citation? Look it up by reference →
                    </Link>
                </header>

                <Form action={bibleSearch.url()} method="get" className="mb-8">
                    {({ processing }) => (
                        <>
                            <div className="flex gap-2">
                                <Input
                                    type="text"
                                    name="q"
                                    defaultValue={query}
                                    autoFocus
                                    placeholder="e.g. amar al prójimo"
                                    className="flex-1"
                                />
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Searching…' : 'Search'}
                                </Button>
                            </div>

                            {processing ? (
                                <div className="mt-8 space-y-4">
                                    {Array.from({ length: 5 }).map((_, i) => (
                                        <Card key={i}>
                                            <CardContent className="space-y-2 py-1">
                                                <Skeleton className="h-4 w-24" />
                                                <Skeleton className="h-4 w-full" />
                                                <Skeleton className="h-4 w-4/5" />
                                            </CardContent>
                                        </Card>
                                    ))}
                                </div>
                            ) : (
                                <Results query={query} results={results} />
                            )}
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

function Results({ query, results }: Props) {
    if (query !== '' && results.length === 0) {
        return (
            <p className="mt-8 text-sm text-muted-foreground">
                No verses found for “{query}”.
            </p>
        );
    }

    if (results.length === 0) {
        return null;
    }

    return (
        <div className="mt-8 space-y-4">
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
        </div>
    );
}
