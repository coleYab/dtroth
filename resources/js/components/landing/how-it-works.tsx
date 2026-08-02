import { Code2, Rocket, ShieldCheck } from 'lucide-react';
import { BlurFade } from '@/components/ui/blur-fade';
import { index as docsIndex } from '@/routes/docs';

const steps = [
    {
        Icon: Rocket,
        step: '01',
        title: 'Create an app',
        description:
            'Register in seconds and generate a sandbox key with simulated identities to test against.',
    },
    {
        Icon: Code2,
        step: '02',
        title: 'Send one request',
        description:
            'Post a subject id and minimum age. Your user grants consent inline - we handle the Fayda handshake.',
    },
    {
        Icon: ShieldCheck,
        step: '03',
        title: 'Get a verdict',
        description:
            'Receive a signed eligible / not-eligible answer and a consent record for your audit trail.',
    },
];

export default function LandingHowItWorks() {
    return (
        <section className="relative mx-auto w-full max-w-6xl px-6 py-16">
            <div className="mb-12 flex flex-col items-center gap-4 text-center">
                <span className="bg-primary/10 text-primary border-primary/20 rounded-full border px-4 py-1.5 text-xs font-semibold uppercase tracking-wider">
                    How it works
                </span>
                <h2 className="max-w-2xl text-balance text-3xl font-bold tracking-tight sm:text-4xl">
                    From first request to production verdict - three steps,
                    minutes apart.
                </h2>
                <p className="text-muted-foreground max-w-xl text-pretty">
                    Create an app, send one request, get a signed verdict - with
                    consent built in at every step.
                </p>
            </div>

            <div className="grid gap-6 md:grid-cols-3">
                {steps.map(({ Icon, step, title, description }, index) => (
                    <BlurFade key={step} delay={0.15 * index} inView>
                        <div className="border-border/60 bg-card/40 hover:border-primary/40 group relative flex h-full flex-col gap-4 rounded-2xl border p-7 backdrop-blur-sm transition-colors duration-300">
                            <div className="flex items-center justify-between">
                                <div className="bg-primary/10 text-primary border-primary/20 flex size-12 items-center justify-center rounded-xl border">
                                    <Icon className="size-6" />
                                </div>
                                <span className="text-foreground/15 text-4xl font-bold">
                                    {step}
                                </span>
                            </div>
                            <h3 className="text-lg font-semibold">{title}</h3>
                            <p className="text-muted-foreground text-sm leading-relaxed">
                                {description}
                            </p>
                        </div>
                    </BlurFade>
                ))}
            </div>

            <div className="mt-10 text-center">
                <a
                    href={docsIndex().url}
                    className="text-primary text-sm font-semibold hover:underline"
                >
                    Read the quickstart guide →
                </a>
            </div>
        </section>
    );
}
