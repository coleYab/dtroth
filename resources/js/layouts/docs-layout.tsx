import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { ThemeToggle } from '@/components/theme-toggle';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as docsIndex } from '@/routes/docs';
import { index as sandboxIndex } from '@/routes/sandbox';

const NAV_ITEMS = [
    { label: 'Documentation', href: docsIndex().url },
    { label: 'Sandbox', href: sandboxIndex().url },
];

export default function DocsLayout({ children }: { children: ReactNode }) {
    const { url } = usePage();

    return (
        <div className="flex min-h-screen flex-col bg-background">
            <header className="sticky top-0 z-40 h-16 border-b bg-background/80 backdrop-blur">
                <div className="flex h-full w-full items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center gap-6">
                        <Link
                            href="/"
                            className="focus-visible:outline-ring flex shrink-0 items-center gap-2 rounded-md focus-visible:outline-2 focus-visible:outline-offset-2"
                        >
                            <div className="bg-primary flex aspect-square size-7 items-center justify-center overflow-hidden rounded-md">
                                <AppLogoIcon className="size-7 object-contain" />
                            </div>
                            <span className="text-base font-semibold tracking-tight">
                                Troth Verify
                            </span>
                        </Link>

                        <nav className="hidden items-center gap-1 md:flex">
                            {NAV_ITEMS.map((item) => {
                                const active = url.startsWith(item.href);

                                return (
                                    <Link
                                        key={item.href}
                                        href={item.href}
                                        prefetch
                                        className={cn(
                                            'rounded-md px-3 py-2 text-sm transition-colors',
                                            active
                                                ? 'bg-accent font-medium text-accent-foreground'
                                                : 'text-muted-foreground hover:bg-accent/60 hover:text-foreground',
                                        )}
                                    >
                                        {item.label}
                                    </Link>
                                );
                            })}
                        </nav>
                    </div>

                    <div className="flex items-center gap-1">
                        <ThemeToggle />
                        <Button type="button" variant="ghost" size="sm" className="text-muted-foreground hover:text-foreground" asChild>
                            <Link href={dashboard()} prefetch>
                                Dashboard
                            </Link>
                        </Button>
                    </div>
                </div>
            </header>

            <main className="w-full flex-1 px-4 py-8 sm:px-6 lg:px-8">{children}</main>
        </div>
    );
}
