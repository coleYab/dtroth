import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { ThemeToggle } from '@/components/theme-toggle';
import { dashboard, login, register } from '@/routes';

const headerLinkClass =
    'inline-flex h-9 items-center justify-center rounded-full px-5 text-sm font-medium transition-colors';

export default function LandingHeader() {
    const { auth } = usePage().props;

    return (
        <header className="border-border/60 bg-background/80 sticky top-0 z-50 w-full border-b backdrop-blur-md">
            <div className="mx-auto w-full max-w-6xl px-6 py-3">
                <nav className="flex items-center justify-between gap-4">
                    <Link
                        href="/"
                        className="focus-visible:outline-ring flex items-center gap-2 rounded-md focus-visible:outline-2 focus-visible:outline-offset-2"
                    >
                        <div className="bg-primary flex aspect-square size-8 items-center justify-center rounded-md">
                            <AppLogoIcon className="size-5 fill-current text-white dark:text-black" />
                        </div>
                        <span className="text-lg font-semibold tracking-tight">
                            Troth Verify
                        </span>
                    </Link>

                    <div className="flex items-center gap-2">
                        <ThemeToggle />
                        {auth.user ? (
                            <Link
                                href={dashboard()}
                                className={`border-border/60 text-foreground hover:bg-muted/60 border ${headerLinkClass}`}
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={login()}
                                    className={`text-muted-foreground hover:text-foreground hover:bg-muted/60 ${headerLinkClass}`}
                                >
                                    Log in
                                </Link>
                                <Link
                                    href={register()}
                                    className={`border-border/60 bg-foreground text-background hover:bg-foreground/90 border ${headerLinkClass}`}
                                >
                                    Register
                                </Link>
                            </>
                        )}
                    </div>
                </nav>
            </div>
        </header>
    );
}
