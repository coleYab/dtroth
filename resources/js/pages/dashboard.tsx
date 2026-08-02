import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, AppWindow, BarChart3, Plus, ShieldCheck } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { EventTypeBadge } from '@/components/event-type-badge';
import { RelativeTime } from '@/components/relative-time';
import { StatCard } from '@/components/stat-card';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { create as createApp } from '@/routes/apps';
import { activity } from '@/routes/dashboard';
import type { ActivityEvent, DashboardStats } from '@/types';

type Props = {
    stats: DashboardStats;
    recentActivity: { data: ActivityEvent[] };
};

export default function Dashboard({ stats, recentActivity }: Props) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-2xl font-semibold">Dashboard</h2>
                        <p className="text-sm text-muted-foreground">
                            An overview of your account activity.
                        </p>
                    </div>
                    <Button asChild className="w-full sm:w-auto">
                        <Link href={createApp()}>
                            <Plus />
                            Create New App
                        </Link>
                    </Button>
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        title="Active Apps"
                        value={stats.activeApps}
                        icon={AppWindow}
                        hint="Applications currently active"
                    />
                    <StatCard
                        title="API Calls"
                        value={stats.apiCalls30d.toLocaleString()}
                        icon={BarChart3}
                        hint="Last 30 days"
                    />
                    <StatCard
                        title="Verifications"
                        value={stats.verifications30d.toLocaleString()}
                        icon={ShieldCheck}
                        hint="Last 30 days"
                    />
                    <StatCard
                        title="Active Alerts"
                        value={stats.activeAlerts}
                        icon={AlertTriangle}
                        hint="Expiring keys and failures"
                        alert={stats.activeAlerts > 0}
                    />
                </div>

                <Card>
                    <CardHeader className="flex-row items-center justify-between space-y-0">
                        <CardTitle>Recent Activity</CardTitle>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={activity()}>View all</Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {recentActivity.data.length === 0 ? (
                            <EmptyState
                                icon={ShieldCheck}
                                title="No activity yet"
                                description="Events such as key rotations, failed verifications, and scope changes will appear here."
                            />
                        ) : (
                            <ul className="divide-y">
                                {recentActivity.data.map((event) => (
                                    <li
                                        key={event.id}
                                        className="flex flex-col gap-1 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4"
                                    >
                                        <div className="flex min-w-0 items-start gap-3">
                                            <EventTypeBadge type={event.type} />
                                            <div className="min-w-0">
                                                <p className="truncate text-sm">{event.description}</p>
                                                {event.app && (
                                                    <p className="text-xs text-muted-foreground">
                                                        {event.app.name}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                        <RelativeTime
                                            date={event.created_at}
                                            className="shrink-0 text-xs text-muted-foreground"
                                        />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
