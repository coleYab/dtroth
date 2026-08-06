import {
    BellRing,
    Fingerprint,
    KeyRound,
    LineChart,
    Lock,
    TestTube2,
} from 'lucide-react';
import { BentoCard, BentoGrid } from '@/components/ui/bento-grid';
import { BorderBeam } from '@/components/ui/border-beam';
import { MagicCard } from '@/components/ui/magic-card';
import { index as docsIndex } from '@/routes/docs';

const features = [
    {
        Icon: Fingerprint,
        name: 'Instant age verification',
        description:
            'One request against the Fayda digital ID confirms whether a user is above any age threshold - in under 200 milliseconds.',
        href: docsIndex().url,
        cta: 'Read the docs',
        className: 'md:col-span-2 md:row-span-2',
    },
    {
        Icon: Lock,
        name: 'Consent-first privacy',
        description:
            'We store only the answer, never the date of birth. Every check is tied to explicit user consent you control.',
        href: docsIndex().url,
        cta: 'Learn more',
        className: '',
    },
    {
        Icon: BellRing,
        name: 'Realtime webhooks',
        description:
            'Get notified the moment a verification completes, with automatic retries and delivery logs.',
        href: docsIndex().url,
        cta: 'Explore webhooks',
        className: '',
    },
    {
        Icon: TestTube2,
        name: 'Sandbox environments',
        description:
            'Develop against a fully simulated Fayda test pool before touching production credentials.',
        href: docsIndex().url,
        cta: 'Try the sandbox',
        className: '',
    },
    {
        Icon: KeyRound,
        name: 'Key rotation & security',
        description:
            'Rotate API keys anytime, revoke instantly, and audit every call with full request logs.',
        href: docsIndex().url,
        cta: 'See security',
        className: '',
    },
    {
        Icon: LineChart,
        name: 'Usage analytics',
        description:
            'Live dashboards for volume, latency, and success rates across every environment.',
        href: docsIndex().url,
        cta: 'View analytics',
        className: '',
    },
];

export default function LandingFeatures() {
    return (
        <section className="relative mx-auto w-full max-w-6xl px-6 py-16">
            <div className="mb-12 flex flex-col items-center gap-4 text-center">
                <span className="bg-primary/10 text-primary border-primary/20 rounded-full border px-4 py-1.5 text-xs font-semibold uppercase tracking-wider">
                    Capabilities
                </span>
                <h2 className="max-w-2xl text-balance text-3xl font-bold tracking-tight sm:text-4xl">
                    Everything a modern verification stack needs
                </h2>
                <p className="text-muted-foreground max-w-xl text-pretty">
                    From first sandbox call to production scale, Troth Verify
                    handles the hard parts of age verification.
                </p>
            </div>

            <BentoGrid className="auto-rows-[18rem] grid-cols-1 md:grid-cols-3">
                {features.map(
                    (
                        { Icon, name, description, href, cta, className },
                        index,
                    ) => (
                        <div key={name} className="relative">
                            <MagicCard
                                mode="orb"
                                gradientSize={260}
                                glowSize={340}
                                glowOpacity={0.35}
                                glowFrom="#FF2D78"
                                glowTo="#00D4A0"
                                className="h-full"
                            >
                                <BentoCard
                                    name={name}
                                    description={description}
                                    href={href}
                                    cta={cta}
                                    Icon={Icon}
                                    className={className}
                                    background={
                                        <div className="from-primary/25 to-accent/25 bg-gradient-to-br opacity-60 transition-opacity duration-300 group-hover:opacity-90" />
                                    }
                                />
                            </MagicCard>
                            {index === 1 && (
                                <BorderBeam size={80} duration={8} />
                            )}
                        </div>
                    ),
                )}
            </BentoGrid>
        </section>
    );
}
