import { BadgeCheck } from 'lucide-react';
import { Marquee } from '@/components/ui/marquee';

const sectors = [
    'Banking',
    'Fintech',
    'Telecom',
    'E-commerce',
    'Marketplaces',
    'Betting & Gaming',
    'Lending',
    'Healthcare',
];

export default function LandingMarquee() {
    return (
        <section className="border-border/60 bg-background/50 relative flex flex-col items-center gap-8 border-y px-6 py-10 backdrop-blur-sm">
            <p className="text-muted-foreground text-xs font-semibold uppercase tracking-[0.2em]">
                Trusted by builders across every regulated industry
            </p>
            <div className="relative w-full max-w-5xl overflow-hidden [mask-image:linear-gradient(to_right,transparent,black_15%,black_85%,transparent)]">
                <Marquee pauseOnHover className="[--duration:30s]">
                    {sectors.map((sector) => (
                        <div
                            key={sector}
                            className="border-border/60 bg-muted/30 text-muted-foreground mx-3 flex items-center gap-2 whitespace-nowrap rounded-full border px-6 py-3 text-sm font-medium"
                        >
                            <BadgeCheck className="text-primary size-4" />
                            {sector}
                        </div>
                    ))}
                </Marquee>
            </div>
        </section>
    );
}
