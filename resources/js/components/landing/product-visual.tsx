import {
    Activity,
    BadgeCheck,
    ShieldCheck,
    Timer,
    XCircle,
    Zap,
} from 'lucide-react';
import { cn } from '@/lib/utils';

const recentChecks = [
    {
        subject: 'fayda-test-a',
        query: '18+',
        result: true,
        time: '2s ago',
    },
    {
        subject: 'fayda-test-c',
        query: '18+',
        result: true,
        time: '47s ago',
    },
    {
        subject: 'fayda-test-b',
        query: '16−',
        result: false,
        time: '3m ago',
    },
    {
        subject: 'fayda-test-d',
        query: '18+',
        result: true,
        time: '8m ago',
    },
];

export default function ProductVisual() {
    return (
        <div className="border-border/60 bg-card/80 shadow-primary/10 relative z-10 w-full overflow-hidden rounded-2xl border shadow-2xl backdrop-blur-sm">
            <div className="border-border/60 flex items-center justify-between border-b px-4 py-2.5">
                <div className="flex items-center gap-1.5">
                    <span className="size-2.5 rounded-full bg-red-500/80" />
                    <span className="size-2.5 rounded-full bg-yellow-500/80" />
                    <span className="size-2.5 rounded-full bg-green-500/80" />
                </div>
                <span className="text-muted-foreground font-mono text-[11px]">
                    app.trothverify.com
                </span>
                <span className="flex items-center gap-1.5 text-[11px] font-medium text-emerald-500">
                    <span className="size-1.5 animate-pulse rounded-full bg-emerald-500" />
                    Live
                </span>
            </div>

            <div className="divide-border/60 border-border/60 grid grid-cols-3 divide-x border-b">
                <div className="flex flex-col items-center gap-1 px-2 py-4">
                    <span className="text-muted-foreground flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-wider">
                        <Activity className="text-primary size-3" />
                        Verifications
                    </span>
                    <span className="text-xl font-bold tabular-nums sm:text-2xl">
                        1,284
                    </span>
                    <span className="text-muted-foreground text-[11px]">
                        today
                    </span>
                </div>
                <div className="flex flex-col items-center gap-1 px-2 py-4">
                    <span className="text-muted-foreground flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-wider">
                        <Timer className="text-accent size-3" />
                        Median
                    </span>
                    <span className="text-xl font-bold tabular-nums sm:text-2xl">
                        142<span className="text-muted-foreground">ms</span>
                    </span>
                    <span className="text-muted-foreground text-[11px]">
                        latency
                    </span>
                </div>
                <div className="flex flex-col items-center gap-1 px-2 py-4">
                    <span className="text-muted-foreground flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-wider">
                        <Zap className="size-3 text-amber-500" />
                        Success
                    </span>
                    <span className="text-xl font-bold tabular-nums sm:text-2xl">
                        99.9%
                    </span>
                    <span className="text-muted-foreground text-[11px]">
                        uptime
                    </span>
                </div>
            </div>

            <div className="p-4 sm:p-5">
                <div className="flex items-center justify-between gap-3 rounded-xl border border-emerald-500/30 bg-emerald-500/[0.06] px-4 py-3">
                    <div className="min-w-0">
                        <p className="flex items-center gap-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                            <ShieldCheck className="size-3.5" />
                            Age 18+ verified
                        </p>
                        <p className="text-muted-foreground mt-0.5 truncate font-mono text-[11px]">
                            fayda-test-a · query 6f0e2a3b…
                        </p>
                    </div>
                    <BadgeCheck className="size-5 shrink-0 text-emerald-500" />
                </div>

                <p className="text-muted-foreground mt-4 text-[11px] font-medium uppercase tracking-wider">
                    Recent verifications
                </p>
                <div className="mt-2 flex flex-col">
                    {recentChecks.map((check) => (
                        <div
                            key={check.subject}
                            className="border-border/60 flex items-center justify-between gap-3 border-b py-2.5 text-sm last:border-b-0"
                        >
                            <div className="flex min-w-0 items-center gap-2">
                                <span className="text-muted-foreground font-mono text-xs">
                                    {check.subject}
                                </span>
                                <span className="border-border/60 text-muted-foreground rounded-md border px-1.5 py-0.5 font-mono text-[10px]">
                                    {check.query}
                                </span>
                            </div>
                            <div className="flex shrink-0 items-center gap-3">
                                <span
                                    className={cn(
                                        'flex items-center gap-1 text-xs font-semibold',
                                        check.result
                                            ? 'text-emerald-600 dark:text-emerald-400'
                                            : 'text-red-500',
                                    )}
                                >
                                    {check.result ? (
                                        <BadgeCheck className="size-3.5" />
                                    ) : (
                                        <XCircle className="size-3.5" />
                                    )}
                                    {String(check.result)}
                                </span>
                                <span className="text-muted-foreground w-12 text-right text-[11px] tabular-nums">
                                    {check.time}
                                </span>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}
