import { BookOpen, Menu, Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { cn } from '@/lib/utils';

interface DocsSection {
    slug: string;
    title: string;
}

interface SearchEntry {
    slug: string;
    title: string;
    text: string;
    headings: string[];
}

interface DocsNavProps {
    sections: DocsSection[];
    searchIndex: SearchEntry[];
    activeSlug: string;
    onSelectSection: (slug: string) => void;
}

function normalize(text: string): string {
    return text.toLowerCase().trim();
}

export function DocsNav({
    sections,
    searchIndex,
    activeSlug,
    onSelectSection,
}: DocsNavProps) {
    const [query, setQuery] = useState('');
    const [sheetOpen, setSheetOpen] = useState(false);

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

                return {
                    section: sections.find((s) => s.slug === entry.slug)!,
                    score,
                };
            })
            .filter((entry) => entry.score > 0)
            .sort((a, b) => b.score - a.score);
    }, [query, sections, searchIndex]);

    const selectFromSheet = (slug: string) => {
        setSheetOpen(false);
        setTimeout(() => onSelectSection(slug), 100);
    };

    const sectionButtons = (onSelect: (slug: string) => void) => (
        <>
            {matches.map(({ section }) => (
                <button
                    key={section.slug}
                    type="button"
                    onClick={() => onSelect(section.slug)}
                    className={cn(
                        'rounded-md px-3 py-2 text-left text-sm transition-colors',
                        activeSlug === section.slug
                            ? 'bg-accent text-accent-foreground font-medium'
                            : 'text-muted-foreground hover:bg-accent/60 hover:text-foreground',
                    )}
                >
                    {section.title}
                </button>
            ))}
            {matches.length === 0 && (
                <p className="text-muted-foreground px-3 py-2 text-sm">
                    No matching sections.
                </p>
            )}
        </>
    );

    return (
        <>
            <aside className="hidden w-60 shrink-0 lg:block">
                <div className="sticky top-20 flex flex-col gap-4">
                    <div className="relative">
                        <Search className="text-muted-foreground pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2" />
                        <Input
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            placeholder="Filter docs..."
                            className="pl-9"
                            aria-label="Search documentation"
                        />
                    </div>
                    <nav className="flex flex-col gap-1">
                        {sectionButtons(onSelectSection)}
                    </nav>
                </div>
            </aside>

            <Card className="sticky top-16 z-20 lg:hidden">
                <CardContent className="p-2">
                    <div className="flex items-center gap-2">
                        <Sheet open={sheetOpen} onOpenChange={setSheetOpen}>
                            <SheetTrigger asChild>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    className="h-8"
                                >
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
                                <div className="mt-4 flex flex-col gap-4 px-3">
                                    <div className="relative">
                                        <Search className="text-muted-foreground pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2" />
                                        <Input
                                            value={query}
                                            onChange={(e) =>
                                                setQuery(e.target.value)
                                            }
                                            placeholder="Filter docs..."
                                            className="pl-9"
                                            aria-label="Search documentation"
                                        />
                                    </div>
                                    <nav className="flex flex-col gap-1">
                                        {sectionButtons(selectFromSheet)}
                                    </nav>
                                </div>
                            </SheetContent>
                        </Sheet>
                        <p className="text-muted-foreground text-sm">
                            {matches.length}{' '}
                            {matches.length === 1 ? 'section' : 'sections'}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </>
    );
}
