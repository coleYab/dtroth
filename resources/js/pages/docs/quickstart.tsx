import { Head } from '@inertiajs/react';
import { FlaskConical, Rocket } from 'lucide-react';
import { useMemo } from 'react';
import { CodeTabs } from '@/components/code-tabs';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { segmentDocHtml } from '@/lib/markdown-segments';
import { index as sandboxIndex } from '@/routes/sandbox';

interface QuickstartContent {
    slug: string;
    title: string;
    html: string;
    headings: { id: string; text: string; level: number }[];
}

interface QuickstartProps {
    content: QuickstartContent;
}

export default function Quickstart({ content }: QuickstartProps) {
    const segments = useMemo(() => segmentDocHtml(content.html), [content.html]);

    return (
        <>
            <Head title="Quickstart" />
            <PageHeader title="Quickstart" description="From signup to your first successful age verification in five minutes." />

            <div className="max-w-3xl">
                <Card className="mb-6">
                    <CardContent className="p-4">
                        <div className="flex items-start gap-3">
                            <Rocket className="mt-0.5 size-5 shrink-0 text-muted-foreground" />
                            <p className="text-sm text-muted-foreground">
                                You'll need a sandbox API key for one of your applications. Head to{' '}
                                <a href="/apps" className="underline underline-offset-3">
                                    Applications
                                </a>{' '}
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

                <a href={sandboxIndex().url} className="mt-8 block">
                    <Button type="button" size="lg" className="w-full gap-2 sm:w-auto">
                        <FlaskConical className="size-4" />
                        Try it in the Sandbox
                    </Button>
                </a>
                <p className="mt-2 text-sm text-muted-foreground">
                    No code, no keys — test real verification requests against mock identities instantly.
                </p>
            </div>
        </>
    );
}
