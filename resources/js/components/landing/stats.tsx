import { NumberTicker } from '@/components/ui/number-ticker';

const stats = [
    { value: 200, suffix: 'ms', label: 'Median verification latency' },
    { value: 99.9, suffix: '%', label: 'Uptime across the API', decimals: 1 },
    { value: 1, suffix: 'M+', label: 'Verifications processed', static: true },
    { value: 24, suffix: '/7', label: 'Support on every plan' },
];

export default function LandingStats() {
    return (
        <section className="border-border/60 bg-muted/20 relative mx-auto w-full max-w-6xl rounded-3xl border px-6 py-12 sm:px-12">
            <div className="grid grid-cols-2 gap-10 lg:grid-cols-4">
                {stats.map(
                    ({ value, suffix, label, decimals, static: isStatic }) => (
                        <div
                            key={label}
                            className="flex flex-col items-center gap-2 text-center"
                        >
                            <span className="text-4xl font-bold tabular-nums tracking-tight sm:text-5xl">
                                {isStatic ? (
                                    value
                                ) : (
                                    <NumberTicker
                                        value={value}
                                        decimalPlaces={decimals ?? 0}
                                    />
                                )}
                                <span className="from-primary to-accent bg-gradient-to-r bg-clip-text text-transparent">
                                    {suffix}
                                </span>
                            </span>
                            <span className="text-muted-foreground max-w-[12rem] text-sm">
                                {label}
                            </span>
                        </div>
                    ),
                )}
            </div>
        </section>
    );
}
