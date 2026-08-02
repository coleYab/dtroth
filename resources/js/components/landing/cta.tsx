import { Link } from '@inertiajs/react';
import { RippleButton } from '@/components/ui/ripple-button';
import { register } from '@/routes';
import { index as docsIndex } from '@/routes/docs';

export default function LandingCta() {
    return (
        <section className="relative mx-auto w-full max-w-6xl px-6 py-16">
            <div className="border-border/60 relative overflow-hidden rounded-3xl border px-6 py-20 text-center sm:px-12">
                <div
                    aria-hidden
                    className="bg-primary/25 absolute -top-32 left-1/2 h-72 w-[36rem] -translate-x-1/2 rounded-full blur-[8rem]"
                />
                <div
                    aria-hidden
                    className="bg-accent/20 absolute -bottom-32 left-1/2 h-72 w-[28rem] -translate-x-1/2 rounded-full blur-[8rem]"
                />
                <div className="relative z-10 flex flex-col items-center gap-6">
                    <h2 className="max-w-2xl text-balance text-3xl font-bold tracking-tight sm:text-5xl">
                        Start verifying in the next five minutes
                    </h2>
                    <p className="text-muted-foreground max-w-xl text-pretty">
                        Create your free account, grab a sandbox key, and ship
                        your first consent-first verification before your coffee
                        goes cold.
                    </p>
                    <div className="flex flex-wrap items-center justify-center gap-4">
                        <Link href={register()}>
                            <RippleButton
                                rippleColor="rgba(255,255,255,0.4)"
                                className="bg-primary text-primary-foreground hover:bg-primary/90 h-12 rounded-full px-8 text-sm font-semibold shadow-lg transition-colors"
                            >
                                Create your account
                            </RippleButton>
                        </Link>
                        <Link
                            href={docsIndex()}
                            className="text-muted-foreground hover:text-foreground inline-flex h-12 items-center justify-center rounded-full border px-8 text-center text-sm font-semibold transition-colors"
                        >
                            Explore the API
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    );
}
