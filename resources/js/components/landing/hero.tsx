import { Link } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { AnimatedGridPattern } from '@/components/ui/animated-grid-pattern';
import { AuroraText } from '@/components/ui/aurora-text';
import { ShimmerButton } from '@/components/ui/shimmer-button';
import { cn } from '@/lib/utils';
import { register } from '@/routes';
import { index as docsIndex } from '@/routes/docs';

export default function LandingHero() {
    return (
        <section className="relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-6 pb-10 pt-16">
            <div
                aria-hidden
                className="bg-primary/20 dark:bg-primary/15 absolute -top-40 left-1/2 h-[36rem] w-[36rem] -translate-x-1/2 rounded-full blur-[10rem]"
            />
            <div
                aria-hidden
                className="bg-accent/25 dark:bg-accent/10 absolute -bottom-48 -left-32 h-[28rem] w-[28rem] rounded-full blur-[10rem]"
            />
            <AnimatedGridPattern
                numSquares={32}
                maxOpacity={0.06}
                duration={3}
                repeatDelay={1}
                className={cn(
                    'mask-[radial-gradient(700px_circle_at_center,white,transparent)]',
                    'inset-x-0 inset-y-[-30%] h-[200%] skew-y-12',
                )}
            />

            <div className="relative z-10 mx-auto flex w-full max-w-7xl flex-col items-center gap-6 text-center">
                <div className="bg-muted/50 border-border/60 inline-flex items-center gap-2 rounded-full border px-4 py-1.5 backdrop-blur-sm">
                    <span className="bg-primary size-1.5 animate-pulse rounded-full" />
                    <span className="text-muted-foreground text-xs font-medium">
                        Built on the Ethiopian Fayda digital ID
                    </span>
                </div>

                <h1 className="max-w-5xl text-balance text-4xl font-bold leading-tight tracking-tight sm:text-6xl lg:text-7xl">
                    Verify age at the{' '}
                    <AuroraText
                        colors={['#FF2D78', '#7928CA', '#00D4A0', '#FF2D78']}
                        className="font-bold"
                    >
                        speed of light
                    </AuroraText>
                </h1>

                <p className="text-muted-foreground max-w-2xl text-pretty text-base sm:text-lg">
                    Troth Verify is the consent-first age verification API for
                    the digital age. Confirm how old your users are in
                    milliseconds - without ever storing their date of birth.
                </p>

                <div className="flex flex-wrap items-center justify-center gap-4">
                    <Link href={register()}>
                        <ShimmerButton className="h-12 px-7 text-center text-sm font-semibold">
                            Start building - it&apos;s free
                        </ShimmerButton>
                    </Link>
                    <Link
                        href={docsIndex()}
                        className="border-border/60 text-muted-foreground hover:text-foreground hover:border-border inline-flex h-12 items-center justify-center rounded-full border px-7 text-center text-sm font-semibold transition-colors"
                    >
                        Read the docs
                    </Link>
                </div>

                <div className="text-muted-foreground mt-2 flex items-center gap-2 text-xs">
                    <ShieldCheck className="text-primary size-4" />
                    No credit card required · Go live in minutes
                </div>
            </div>
        </section>
    );
}
