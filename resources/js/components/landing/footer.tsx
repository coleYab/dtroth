import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { index as docsIndex } from '@/routes/docs';
import { terms, privacyPolicy } from '@/routes/legal';
import { index as supportIndex } from '@/routes/support';

const columns = [
    {
        title: 'Product',
        links: [
            { label: 'Documentation', href: docsIndex().url },
            { label: 'Support', href: supportIndex().url },
        ],
    },
    {
        title: 'Legal',
        links: [
            { label: 'Terms of service', href: terms().url },
            { label: 'Privacy policy', href: privacyPolicy().url },
        ],
    },
];

export default function LandingFooter() {
    return (
        <footer className="border-border/60 bg-background/60 relative border-t px-6 py-12 backdrop-blur-sm">
            <div className="mx-auto grid w-full max-w-6xl gap-10 sm:grid-cols-2 lg:grid-cols-3">
                <div className="flex flex-col gap-3">
                    <div className="flex items-center gap-2">
                        <div className="bg-primary flex aspect-square size-8 items-center justify-center rounded-md">
                            <AppLogoIcon className="size-5 fill-current text-white dark:text-black" />
                        </div>
                        <span className="text-lg font-semibold">
                            Troth Verify
                        </span>
                    </div>
                    <p className="text-muted-foreground max-w-xs text-sm">
                        Consent-first identity verification, powered by the
                        Ethiopian Fayda digital ID.
                    </p>
                </div>
                {columns.map((column) => (
                    <div key={column.title} className="flex flex-col gap-3">
                        <span className="text-xs font-semibold uppercase tracking-widest">
                            {column.title}
                        </span>
                        {column.links.map((link) => (
                            <Link
                                key={link.href}
                                href={link.href}
                                className="text-muted-foreground hover:text-foreground w-fit text-sm transition-colors"
                            >
                                {link.label}
                            </Link>
                        ))}
                    </div>
                ))}
            </div>
            <div className="border-border/60 text-muted-foreground mx-auto mt-12 flex w-full max-w-6xl flex-col items-center justify-between gap-3 border-t pt-6 text-xs sm:flex-row">
                <span>
                    © {new Date().getFullYear()} Troth Verify. All rights
                    reserved.
                </span>
                <span>Built for the digital era of Ethiopia.</span>
            </div>
        </footer>
    );
}
