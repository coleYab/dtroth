import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { BarChart3, KeyRound, ShieldCheck, Users } from 'lucide-react';
import {
    AppSectionHeader,
    AppSectionLayout,
    appSectionNavItems,
} from '@/components/app-section-layout';
import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index as appsIndex } from '@/routes/apps';
import { index as keysIndex } from '@/routes/apps/keys';
import { shortAppName } from '@/lib/utils';
import type { ApiKey, DeveloperApp } from '@/types';

type Props = {
    app: DeveloperApp;
    stats: {
        apiCalls: number;
        lastVerification: string | null;
        activeConsents: number;
    };
    keys: { data: ApiKey[] };
};

function formatDate(date: string | null): string {
    if (!date) {
        return '—';
    }

    return new Date(date).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

export default function ShowApp({ app, stats, keys }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Applications', href: appsIndex() },
            { title: shortAppName(app.name), href: `/apps/${app.id}` },
        ],
    });

    return (
        <>
            <Head title={app.name} />

            <div className="flex flex-col gap-6">
                <AppSectionHeader
                    app={app}
                    description="Overview of this application's activity and configuration."
                />

                <AppSectionLayout
                    items={appSectionNavItems(app)}
                    active="Overview"
                    label="Application"
                >
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <StatCard
                            title="API Calls"
                            value={stats.apiCalls.toLocaleString()}
                            icon={BarChart3}
                        />
                        <StatCard
                            title="Last Verification"
                            value={formatDate(stats.lastVerification)}
                            icon={ShieldCheck}
                        />
                        <StatCard
                            title="Active Consents"
                            value={stats.activeConsents}
                            icon={Users}
                        />
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Application details</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="divide-y text-sm">
                                <div className="flex flex-col gap-1 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                                    <dt className="text-muted-foreground">
                                        Created
                                    </dt>
                                    <dd>{formatDate(app.created_at)}</dd>
                                </div>
                                <div className="flex flex-col gap-1 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                                    <dt className="text-muted-foreground">
                                        Status
                                    </dt>
                                    <dd className="capitalize">{app.status}</dd>
                                </div>
                                <div className="flex flex-col gap-1 py-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                                    <dt className="text-muted-foreground">
                                        Redirect URIs
                                    </dt>
                                    <dd className="text-right">
                                        {app.redirect_uris.length > 0 ? (
                                            <ul className="space-y-1">
                                                {app.redirect_uris.map(
                                                    (uri) => (
                                                        <li
                                                            key={uri}
                                                            className="break-all"
                                                        >
                                                            {uri}
                                                        </li>
                                                    ),
                                                )}
                                            </ul>
                                        ) : (
                                            '—'
                                        )}
                                    </dd>
                                </div>
                                <div className="flex flex-col gap-1 py-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                                    <dt className="text-muted-foreground">
                                        Allowed origins
                                    </dt>
                                    <dd className="text-right">
                                        {app.allowed_origins.length > 0 ? (
                                            <ul className="space-y-1">
                                                {app.allowed_origins.map(
                                                    (origin) => (
                                                        <li
                                                            key={origin}
                                                            className="break-all"
                                                        >
                                                            {origin}
                                                        </li>
                                                    ),
                                                )}
                                            </ul>
                                        ) : (
                                            '—'
                                        )}
                                    </dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex-row items-center justify-between space-y-0">
                            <CardTitle>Recent API Keys</CardTitle>
                            <Button variant="ghost" size="sm" asChild>
                                <Link href={keysIndex(app.id)}>View all</Link>
                            </Button>
                        </CardHeader>
                        <CardContent>
                            {keys.data.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No API keys have been issued for this
                                    application yet.
                                </p>
                            ) : (
                                <ul className="divide-y text-sm">
                                    {keys.data.map((key) => (
                                        <li
                                            key={key.id}
                                            className="flex flex-col gap-1 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4"
                                        >
                                            <div className="flex min-w-0 items-center gap-3">
                                                <KeyRound className="size-4 shrink-0 text-muted-foreground" />
                                                <div className="min-w-0">
                                                    <p className="truncate font-medium">
                                                        {key.name}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {key.environment ===
                                                        'production'
                                                            ? 'Production'
                                                            : key.environment ===
                                                                'sandbox'
                                                              ? 'Sandbox'
                                                              : 'Shared'}
                                                        {' • '}
                                                        {key.is_revoked
                                                            ? 'Revoked'
                                                            : key.expires_at
                                                              ? `Expires ${formatDate(key.expires_at)}`
                                                              : 'Never expires'}
                                                    </p>
                                                </div>
                                            </div>
                                            <Badge
                                                variant={
                                                    key.is_revoked
                                                        ? 'destructive'
                                                        : 'secondary'
                                                }
                                                className="w-fit"
                                            >
                                                {key.is_revoked
                                                    ? 'Revoked'
                                                    : key.last_used_at
                                                      ? 'In use'
                                                      : 'Unused'}
                                            </Badge>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </AppSectionLayout>
            </div>
        </>
    );
}
