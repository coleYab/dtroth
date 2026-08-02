import { Head, router } from '@inertiajs/react';
import { BookOpen, Check, LifeBuoy, Menu, PenLine, Search, ThumbsDown, ThumbsUp } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { CodeTabs } from '@/components/code-tabs';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { segmentDocHtml } from '@/lib/markdown-segments';
import { cn } from '@/lib/utils';
import { feedback, quickstart } from '@/routes/docs';

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

function normalize(text: string): string {
    return text.toLowerCase().trim();
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
                <a
                    href={`/support?subject=Docs%3A%20${encodeURIComponent(section.title)}`}
                    className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                >
                    <PenLine className="size-3.5" />
                    Edit this page
                </a>
            </div>
            <FeedbackWidget slug={section.slug} />
        </section>
    );
}

export default function Docs({ sections, searchIndex }: DocsProps) {
    const [query, setQuery] = useState('');
    const [sheetOpen, setSheetOpen] = useState(false);
    const [activeSection, setActiveSection] = useState(sections[0]?.slug ?? '');
    const contentRef = useRef<HTMLDivElement>(null);

    const matches = useMemo(() => {
        const q = normalize(query);

        if (q === '') {
            return sections.map((section) => ({ section, score: 0 }));
        }

        return searchIndex
            .map((entry) => {
                let score = 0;
                const haystack = `${normalize(entry.title)} ${entry.headings.join(' ').toLowerCase()} ${normalize(entry.text)}`;

                if (normalize(entry.title).includes(q)) {
                    score += 10;
                }

                entry.headings.forEach((heading) => {
                    if (normalize(heading).includes(q)) {
                        score += 3;
                    }
                });

                if (haystack.includes(q)) {
                    score += 1;
                }

                return { section: sections.find((s) => s.slug === entry.slug)!, score };
            })
            .filter((entry) => entry.score > 0)
            .sort((a, b) => b.score - a.score);
    }, [query, sections, searchIndex]);

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
    };

    const navigateToFromSheet = (slug: string) => {
        setSheetOpen(false);
        setTimeout(() => navigateTo(slug), 100);
    };

    return (
        <>
            <Head title="API Reference" />
            <PageHeader title="API Reference" description="Everything you need to integrate the Troth Age Verification API." />

            <div className="lg:flex lg:gap-8">
                <div className="hidden w-60 shrink-0 lg:block">
                    <div className="sticky top-24 flex flex-col gap-4">
                        <div className="relative">
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                value={query}
                                onChange={(e) => setQuery(e.target.value)}
                                placeholder="Filter docs..."
                                className="pl-9"
                                aria-label="Search documentation"
                            />
                        </div>
                        <nav className="flex flex-col gap-1">
                            {matches.map(({ section }) => (
                                <button
                                    key={section.slug}
                                    type="button"
                                    onClick={() => navigateTo(section.slug)}
                                    className={cn(
                                        'rounded-md px-3 py-2 text-left text-sm transition-colors',
                                        activeSection === section.slug
                                            ? 'bg-accent font-medium text-accent-foreground'
                                            : 'text-muted-foreground hover:bg-accent/60 hover:text-foreground',
                                    )}
                                >
                                    {section.title}
                                </button>
                            ))}
                            {matches.length === 0 && (
                                <p className="px-3 py-2 text-sm text-muted-foreground">No matching sections.</p>
                            )}
                        </nav>
                        <a
                            href={quickstart().url}
                            className="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm text-muted-foreground hover:bg-accent/60 hover:text-foreground"
                        >
                            <BookOpen className="size-4" />
                            Quickstart
                        </a>
                    </div>
                </div>

                <div ref={contentRef} className="min-w-0 flex-1">
                    <Card className="sticky top-16 z-20 lg:hidden">
                        <CardContent className="space-y-3 p-3">
                            <div className="flex items-center gap-2">
                                <Sheet open={sheetOpen} onOpenChange={setSheetOpen}>
                                    <SheetTrigger asChild>
                                        <Button type="button" variant="outline" size="sm">
                                            <Menu className="size-4" />
                                            Menu
                                        </Button>
                                    </SheetTrigger>
                                    <SheetContent side="left" className="w-[300px]">
                                        <SheetHeader>
                                            <SheetTitle className="flex items-center gap-2">
                                                <BookOpen className="size-4" />
                                                Documentation
                                            </SheetTitle>
                                        </SheetHeader>
                                        <div className="mt-4 flex flex-col gap-4">
                                            <div className="relative">
                                                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                                <Input
                                                    value={query}
                                                    onChange={(e) => setQuery(e.target.value)}
                                                    placeholder="Filter docs..."
                                                    className="pl-9"
                                                    aria-label="Search documentation"
                                                />
                                            </div>
                                            <nav className="flex flex-col gap-1">
                                                {matches.map(({ section }) => (
                                                    <button
                                                        key={section.slug}
                                                        type="button"
                                                        onClick={() => navigateToFromSheet(section.slug)}
                                                        className={cn(
                                                            'rounded-md px-3 py-2 text-left text-sm',
                                                            activeSection === section.slug
                                                                ? 'bg-accent font-medium text-accent-foreground'
                                                                : 'text-muted-foreground hover:bg-accent/60 hover:text-foreground',
                                                        )}
                                                    >
                                                        {section.title}
                                                    </button>
                                                ))}
                                                {matches.length === 0 && (
                                                    <p className="px-3 py-2 text-sm text-muted-foreground">
                                                        No matching sections.
                                                    </p>
                                                )}
                                            </nav>
                                            <a
                                                href={quickstart().url}
                                                className="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm text-muted-foreground hover:bg-accent/60 hover:text-foreground"
                                            >
                                                <BookOpen className="size-4" />
                                                Quickstart
                                            </a>
                                        </div>
                                    </SheetContent>
                                </Sheet>
                                <p className="text-sm text-muted-foreground">
                                    {matches.length} {matches.length === 1 ? 'section' : 'sections'}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="mt-4 flex flex-col gap-10 lg:mt-0">
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
                                    <a href={quickstart().url} className="underline underline-offset-3">
                                        Quickstart
                                    </a>{' '}
                                    or open a{' '}
                                    <a href="/support" className="underline underline-offset-3">
                                        support ticket
                                    </a>
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
