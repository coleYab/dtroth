import { Head, setLayoutProps } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { BarChart3, Wallet } from 'lucide-react';
import { StatCard } from '@/components/stat-card';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { UsageByQueryChart } from '@/components/usage-charts';
import { show as showApp } from '@/routes/apps';
import billing from '@/routes/billing';
import { index as billingIndex, usage as usageRoute } from '@/routes/billing';
import type { BillingUsageApp, BillingUsageCycle } from '@/types';

type Props = {
    cycle: BillingUsageCycle;
    apps: BillingUsageApp[];
};

export default function BillingUsage({ cycle, apps }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Billing', href: billingIndex() },
            { title: 'Usage', href: usageRoute() },
        ],
    });

    const periodLabel = `${new Date(cycle.period_start).toLocaleDateString()} — ${new Date(cycle.period_end).toLocaleDateString()}`;

    const chartData = apps.map((app) => ({
        query: app.app_name,
        calls: app.calls,
    }));

    return (
        <>
            <Head title="Billing usage" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 className="text-2xl font-semibold">Usage</h2>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Billable usage for the current cycle ({periodLabel}
                            ).
                        </p>
                    </div>
                    <Link
                        href={billing.index().url}
                        className="text-primary flex items-center gap-1 text-sm font-medium hover:underline"
                    >
                        <Wallet className="size-4" />
                        Back to billing
                    </Link>
                </div>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-4">
                    <StatCard
                        title="Total calls"
                        value={cycle.total_calls.toLocaleString()}
                        icon={BarChart3}
                    />
                    <StatCard
                        title="Verifications"
                        value={cycle.total_verifications.toLocaleString()}
                        icon={BarChart3}
                    />
                    <StatCard
                        title="Estimated cost"
                        value={cycle.cost_formatted}
                        icon={Wallet}
                    />
                    <StatCard
                        title="Days remaining"
                        value={Math.max(
                            0,
                            Math.ceil(cycle.days_remaining),
                        ).toLocaleString()}
                        icon={BarChart3}
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Cycle usage</CardTitle>
                        <CardDescription>
                            {cycle.usage_percent}% of your plan's included calls
                            used.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Progress
                            value={cycle.usage_percent}
                            className="h-2.5"
                        />
                        <p className="text-muted-foreground mt-2 text-xs">
                            {cycle.total_calls.toLocaleString()} of{' '}
                            {cycle.plan_calls.toLocaleString()} calls used in
                            this cycle.
                        </p>
                    </CardContent>
                </Card>

                {apps.length > 1 && <UsageByQueryChart data={chartData} />}

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Per application
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-3">
                        {apps.map((app) => (
                            <Link
                                key={app.app_id}
                                href={showApp(app.app_id).url}
                                className="hover:border-muted-foreground/40 flex items-center justify-between gap-3 rounded-lg border p-3 transition-colors"
                            >
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-medium">
                                        {app.app_name}
                                    </p>
                                    <p className="text-muted-foreground text-xs">
                                        {app.verifications.toLocaleString()}{' '}
                                        verifications
                                    </p>
                                </div>
                                <div className="text-right">
                                    <p className="text-sm font-semibold tabular-nums">
                                        {app.calls.toLocaleString()} calls
                                    </p>
                                    <p className="text-muted-foreground text-xs tabular-nums">
                                        {app.estimated_cost_etb > 0
                                            ? `ETB ${(app.estimated_cost_etb / 100).toFixed(2)}`
                                            : 'No overage'}
                                    </p>
                                </div>
                            </Link>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
