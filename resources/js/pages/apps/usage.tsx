import { Head, router, setLayoutProps } from '@inertiajs/react';
import { Activity, CheckCircle2, Hash, Layers } from 'lucide-react';
import { useState } from 'react';
import { AppSectionHeader, AppSectionLayout, appSectionNavItems } from '@/components/app-section-layout';
import { StatCard } from '@/components/stat-card';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { UsageAreaChart, UsageByQueryChart } from '@/components/usage-charts';
import { dashboard } from '@/routes';
import { index as appsIndex, usage as usageRoute } from '@/routes/apps';
import { logs as logsRoute } from '@/routes/apps';
import type { DeveloperApp, UsageMetrics } from '@/types';

type Props = {
    app: DeveloperApp;
    range: string;
    metrics: UsageMetrics;
    rangeLabel: string;
};

export default function Usage({ app, range, metrics, rangeLabel }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Applications', href: appsIndex() },
            { title: app.name, href: `/apps/${app.id}` },
        ],
    });

    const [from, setFrom] = useState('');
    const [to, setTo] = useState('');

    function selectRange(next: string): void {
        router.get(usageRoute(app.id).url, { range: next }, { preserveState: true });
    }

    function applyCustom(): void {
        if (from && to) {
            router.get(
                usageRoute(app.id).url,
                { range: 'custom', from, to },
                { preserveState: true },
            );
        }
    }

    return (
        <>
            <Head title={`${app.name} · Usage`} />

            <div className="flex flex-col gap-6">
                <AppSectionHeader
                    app={app}
                    description="API call and verification volume for this application."
                />

                <AppSectionLayout items={appSectionNavItems(app)} active="Usage" label="Application">
                    <div className="grid gap-6">
                        <Card>
                            <CardHeader className="flex flex-row flex-wrap items-center justify-between gap-4">
                                <div>
                                    <CardTitle className="text-base">Time range</CardTitle>
                                    <CardDescription>{rangeLabel}</CardDescription>
                                </div>
                                <ToggleGroup
                                    type="single"
                                    value={range}
                                    onValueChange={(value) => {
                                        if (value) {
                                            selectRange(value);
                                        }
                                    }}
                                >
                                    <ToggleGroupItem value="7">7 days</ToggleGroupItem>
                                    <ToggleGroupItem value="30">30 days</ToggleGroupItem>
                                    <ToggleGroupItem value="90">90 days</ToggleGroupItem>
                                    <ToggleGroupItem value="custom">Custom</ToggleGroupItem>
                                </ToggleGroup>
                            </CardHeader>
                            {range === 'custom' && (
                                <CardContent className="grid gap-3 sm:grid-cols-[auto_auto_auto] sm:items-end">
                                    <div className="grid gap-2">
                                        <Label htmlFor="usage-from">From</Label>
                                        <Input
                                            id="usage-from"
                                            type="date"
                                            value={from}
                                            onChange={(event) => setFrom(event.target.value)}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="usage-to">To</Label>
                                        <Input
                                            id="usage-to"
                                            type="date"
                                            value={to}
                                            onChange={(event) => setTo(event.target.value)}
                                        />
                                    </div>
                                    <Button onClick={applyCustom} disabled={!from || !to}>
                                        Apply
                                    </Button>
                                </CardContent>
                            )}
                        </Card>

                        <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                            <StatCard
                                title="Total API calls"
                                value={metrics.total_calls.toLocaleString()}
                                icon={Activity}
                            />
                            <StatCard
                                title="Verifications"
                                value={metrics.total_verifications.toLocaleString()}
                                icon={Layers}
                            />
                            <StatCard
                                title="Success rate"
                                value={`${metrics.success_rate}%`}
                                icon={CheckCircle2}
                            />
                            <StatCard
                                title="Most-used query"
                                value={metrics.most_used_query ?? '—'}
                                icon={Hash}
                            />
                        </div>

                        <UsageAreaChart data={metrics.daily} />

                        <UsageByQueryChart data={metrics.by_query} />

                        <p className="text-center text-sm text-muted-foreground">
                            <a
                                href={logsRoute(app.id).url}
                                className="font-medium text-primary hover:underline"
                            >
                                View raw API call logs →
                            </a>
                        </p>
                    </div>
                </AppSectionLayout>
            </div>
        </>
    );
}
