import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import { cn } from '@/lib/utils';
import type { PaginationLink as PaginationLinkType } from '@/types';

export function AppPagination({ links }: { links: PaginationLinkType[] }) {
    if (links.length <= 3) {
        return null;
    }

    const previous = links[0];
    const next = links[links.length - 1];
    const pages = links.slice(1, -1);

    return (
        <Pagination className="mt-6">
            <PaginationContent>
                <PaginationItem>
                    {previous.url ? (
                        <PaginationPrevious href={previous.url} />
                    ) : (
                        <span
                            aria-disabled="true"
                            className="pointer-events-none inline-flex h-9 items-center gap-1 rounded-md px-2.5 text-sm opacity-50"
                        >
                            Previous
                        </span>
                    )}
                </PaginationItem>

                {pages.map((link, index) => (
                    <PaginationItem key={`${link.label}-${index}`}>
                        {link.url ? (
                            <PaginationLink href={link.url} isActive={link.active}>
                                <span
                                    className={cn(
                                        link.label.includes('Previous') || link.label.includes('Next') ? 'sr-only' : '',
                                    )}
                                >
                                    {link.label}
                                </span>
                                <span aria-hidden="true">
                                    {link.label.match(/\d+/)?.[0] ?? link.label}
                                </span>
                            </PaginationLink>
                        ) : (
                            <PaginationEllipsis />
                        )}
                    </PaginationItem>
                ))}

                <PaginationItem>
                    {next.url ? (
                        <PaginationNext href={next.url} />
                    ) : (
                        <span
                            aria-disabled="true"
                            className="pointer-events-none inline-flex h-9 items-center gap-1 rounded-md px-2.5 text-sm opacity-50"
                        >
                            Next
                        </span>
                    )}
                </PaginationItem>
            </PaginationContent>
        </Pagination>
    );
}
