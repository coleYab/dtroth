import { Head, Link, router } from '@inertiajs/react';
import { FlaskConical, Rocket } from 'lucide-react';
import { useMemo } from 'react';
import { CodeTabs } from '@/components/code-tabs';
import { DocsNav } from '@/components/docs-nav';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { segmentDocHtml } from '@/lib/markdown-segments';
import { index as appsIndex } from '@/routes/apps';
import { index as docsIndex } from '@/routes/docs';
import { index as sandboxIndex } from '@/routes/sandbox';

interface DocSection {
    slug: string;
    title: string;
}

interface SearchEntry {
    slug: string;
    title: string;
    text: string;
    headings: string[];
}

interface QuickstartContent {
    slug: string;
    title: string;
    html: string;
    headings: { id: string; text: string; level: number }[];
}

interface QuickstartProps {
    content: QuickstartContent;
    sections: DocSection[];
    searchIndex: SearchEntry[];
}

export default function Quickstart({ content, sections, searchIndex }: QuickstartProps) {
    const segments = useMemo(() => segmentDocHtml(content.html), [content.html]);

    const navigateToSection = (slug: string) => {
        router.visit(`${docsIndex().url}#${slug}`);
    };

    return (
        <>
            <Head title="Quickstart" />

            <div className="lg:flex lg:gap-8">
                <DocsNav
                    sections={sections}
                    searchIndex={searchIndex}
                    activeSlug="quickstart"
                    onSelectSection={navigateToSection}
                />
                <div className="min-w-0 flex-1">
                    <PageHeader
                        title="Quickstart"
                        description="From signup to your first successful age verification in five minutes."
                    />
                    <Card className="mt-6 mb-6">
                        <CardContent className="p-4">
                            <div className="flex items-start gap-3">
                                <Rocket className="mt-0.5 size-5 shrink-0 text-muted-foreground" />
                                <p className="text-sm text-muted-foreground">
                                    You'll need a sandbox API key for one of your applications. Head to{' '}
                                    <Link href={appsIndex().url} prefetch className="underline underline-offset-3">
                                        Applications
                                    </Link>{' '}
                                    to create one if you haven't already.
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    {segments.map((segment, index) =>
                        segment.type === 'code-group' ? (
                            <CodeTabs key={index} items={segment.items} />
                        ) : (
                            <div
                                key={index}
                                className="doc-content max-w-none"
                                dangerouslySetInnerHTML={{ __html: segment.html }}
                            />
                        ),
                    )}

                    <Link href={sandboxIndex().url} prefetch="mount" className="mt-8 block">
                        <Button type="button" size="lg" className="w-full gap-2 sm:w-auto">
                            <FlaskConical className="size-4" />
                            Try it in the Sandbox
                        </Button>
                    </Link>
                    <p className="mt-2 text-sm text-muted-foreground">
                        No code, no keys — test real verification requests against mock identities instantly.
                    </p>
                </div>
            </div>
        </>
    );
}
