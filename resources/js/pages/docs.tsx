import { Head, Link, router } from '@inertiajs/react';
import { Check, LifeBuoy, PenLine, ThumbsDown, ThumbsUp } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { CodeTabs } from '@/components/code-tabs';
import { DocsNav } from '@/components/docs-nav';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { segmentDocHtml } from '@/lib/markdown-segments';
import { feedback, quickstart } from '@/routes/docs';
import { index as supportIndex } from '@/routes/support';

interface DocHeading {
    id: string;
    text: string;
    level: number;
}

interface DocSection {
    slug: string;
    title: string;
    html: string;
    headings: DocHeading[];
}

interface SearchEntry {
    slug: string;
    title: string;
    text: string;
    headings: string[];
}

interface DocsProps {
    sections: DocSection[];
    searchIndex: SearchEntry[];
}

function FeedbackWidget({ slug }: { slug: string }) {
    const [selected, setSelected] = useState<boolean | null>(null);
    const [comment, setComment] = useState('');
    const [sent, setSent] = useState(false);
    const [sending, setSending] = useState(false);

    const submit = (helpful: boolean) => {
        setSelected(helpful);
        setSending(true);
        router.post(
            feedback().url,
            { slug, helpful, comment },
            {
                preserveScroll: true,
                onSuccess: () => setSent(true),
                onFinish: () => setSending(false),
            },
        );
    };

    if (sent) {
        return (
            <div className="mt-8 flex items-center gap-2 rounded-lg border bg-muted/40 px-4 py-3 text-sm text-muted-foreground">
                <Check className="size-4 text-emerald-500" />
                Thanks for the feedback!
            </div>
        );
    }

    return (
        <div className="mt-8 rounded-lg border p-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <p className="text-sm font-medium">Was this page helpful?</p>
                <div className="flex gap-2">
                    <Button
                        type="button"
                        variant={selected === true ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => submit(true)}
                        disabled={sending}
                    >
                        <ThumbsUp className="size-4" />
                        Yes
                    </Button>
                    <Button
                        type="button"
                        variant={selected === false ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => submit(false)}
                        disabled={sending}
                    >
                        <ThumbsDown className="size-4" />
                        No
                    </Button>
                </div>
            </div>
            {selected === false && (
                <div className="mt-3 flex flex-col gap-2 sm:flex-row">
                    <Input
                        value={comment}
                        onChange={(e) => setComment(e.target.value)}
                        placeholder="What's missing? (optional)"
                        className="sm:flex-1"
                    />
                    <Button type="button" size="sm" variant="secondary" onClick={() => submit(false)} disabled={sending}>
                        Send
                    </Button>
                </div>
            )}
        </div>
    );
}

function SectionContent({ section }: { section: DocSection }) {
    const segments = useMemo(() => segmentDocHtml(section.html), [section.html]);

    return (
        <section id={section.slug} data-doc-section={section.slug} className="scroll-mt-24">
            <h1 className="text-2xl font-semibold tracking-tight">{section.title}</h1>
            {segments.map((segment, index) =>
                segment.type === 'code-group' ? (
                    <CodeTabs key={index} items={segment.items} className="mt-4" />
                ) : (
                    <div key={index} className="doc-content mt-4" dangerouslySetInnerHTML={{ __html: segment.html }} />
                ),
            )}
            <div className="mt-8 flex flex-col gap-2 border-t pt-4 sm:flex-row sm:items-center sm:justify-between">
                <Link
                    href={supportIndex({ query: { subject: `Docs: ${section.title}` } }).url}
                    prefetch
                    className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                >
                    <PenLine className="size-3.5" />
                    Edit this page
                </Link>
            </div>
            <FeedbackWidget slug={section.slug} />
        </section>
    );
}

export default function Docs({ sections, searchIndex }: DocsProps) {
    const [activeSection, setActiveSection] = useState(sections[0]?.slug ?? '');
    const contentRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const root = contentRef.current;

        if (root === null) {
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                const visible = entries
                    .filter((entry) => entry.isIntersecting)
                    .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);

                if (visible.length > 0) {
                    setActiveSection(visible[0].target.getAttribute('data-doc-section') ?? '');
                }
            },
            { rootMargin: '-25% 0px -65% 0px' },
        );

        root.querySelectorAll('[data-doc-section]').forEach((el) => observer.observe(el));

        return () => observer.disconnect();
    }, []);

    const navigateTo = (slug: string) => {
        document.getElementById(slug)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        history.replaceState(null, '', `#${slug}`);
    };

    useEffect(() => {
        const hash = window.location.hash.slice(1);

        if (hash !== '' && document.getElementById(hash) !== null) {
            document.getElementById(hash)?.scrollIntoView({ block: 'start' });
        }
    }, []);

    return (
        <>
            <Head title="API Reference" />

            <div className="lg:flex lg:gap-8">
                <DocsNav
                    sections={sections}
                    searchIndex={searchIndex}
                    activeSlug={activeSection}
                    onSelectSection={navigateTo}
                />
                <div ref={contentRef} className="min-w-0 flex-1">
                    <PageHeader
                        title="API Reference"
                        description="Everything you need to integrate the Troth Age Verification API."
                    />
                    <div className="mt-6 flex flex-col gap-10">
                        {sections.map((section) => (
                            <SectionContent key={section.slug} section={section} />
                        ))}
                    </div>

                    <div className="mt-10 rounded-lg border p-4">
                        <div className="flex items-start gap-3">
                            <LifeBuoy className="mt-0.5 size-5 shrink-0 text-muted-foreground" />
                            <div>
                                <p className="text-sm font-medium">Still stuck?</p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Can't find what you're looking for? Check the{' '}
                                    <Link href={quickstart().url} prefetch className="underline underline-offset-3">
                                        Quickstart
                                    </Link>{' '}
                                    or open a{' '}
                                    <Link href={supportIndex().url} prefetch className="underline underline-offset-3">
                                        support ticket
                                    </Link>
                                    .
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
