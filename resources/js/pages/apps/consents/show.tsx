import { Head, setLayoutProps } from '@inertiajs/react';
import { CheckCircle2, Clock3, RefreshCw, XCircle } from 'lucide-react';
import {
    AppSectionHeader,
    AppSectionLayout,
    appSectionNavItems,
} from '@/components/app-section-layout';
import { ConsentStatusBadge } from '@/components/consent-status-badge';
import { RelativeTime } from '@/components/relative-time';
import { ResultBadge } from '@/components/result-badge';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { shortAppName, shortId } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as appsIndex } from '@/routes/apps';
import type { Consent, DeveloperApp } from '@/types';

type Props = {
    app: DeveloperApp;
    consent: Consent;
};

const eventLabels: Record<
    string,
    { label: string; icon: typeof CheckCircle2 }
> = {
    granted: { label: 'Consent granted', icon: CheckCircle2 },
    renewed: { label: 'Consent renewed', icon: RefreshCw },
    revoked: { label: 'Consent revoked', icon: XCircle },
    expired: { label: 'Consent expired', icon: Clock3 },
};

export default function ConsentDetail({ app, consent }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Applications', href: appsIndex() },
            { title: shortAppName(app.name), href: `/apps/${app.id}` },
        ],
    });

    const rows = [
        {
            label: 'User identifier',
            value: shortId(consent.subject_identifier),
            mono: true,
        },
        {
            label: 'Age-query sent',
            value: consent.age_query ?? '—',
            mono: true,
        },
        { label: 'Result', value: null, result: consent.result },
        { label: 'Status', value: null, status: consent.status },
        {
            label: 'Created',
            value: consent.created_at
                ? new Date(consent.created_at).toLocaleString()
                : '—',
        },
        {
            label: 'Expires',
            value: consent.expires_at
                ? new Date(consent.expires_at).toLocaleString()
                : '—',
        },
        {
            label: 'Revoked',
            value: consent.revoked_at
                ? new Date(consent.revoked_at).toLocaleString()
                : '—',
        },
    ];

    return (
        <>
            <Head title={`${app.name} · Consent`} />

            <div className="flex flex-col gap-6">
                <AppSectionHeader
                    app={app}
                    description="Read-only record of a single consent and its lifecycle."
                />

                <AppSectionLayout
                    items={appSectionNavItems(app)}
                    active="Consents"
                    label="Application"
                >
                    <div className="grid gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Consent record</CardTitle>
                                <CardDescription>
                                    Only the age-query pair and its boolean
                                    result are stored — never the date of birth.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="grid gap-4">
                                {rows.map((row) => (
                                    <div
                                        key={row.label}
                                        className="flex flex-wrap items-center justify-between gap-2 border-b pb-3 last:border-b-0 last:pb-0"
                                    >
                                        <span className="text-sm text-muted-foreground">
                                            {row.label}
                                        </span>
                                        {row.result !== undefined ? (
                                            <ResultBadge
                                                result={row.result ?? null}
                                            />
                                        ) : row.status !== undefined ? (
                                            <ConsentStatusBadge
                                                status={row.status ?? 'active'}
                                            />
                                        ) : (
                                            <span
                                                className={`text-sm font-medium ${row.mono ? 'font-mono text-xs' : ''}`}
                                            >
                                                {row.value}
                                            </span>
                                        )}
                                    </div>
                                ))}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Timeline</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ol className="relative grid gap-6 border-l pl-6">
                                    {consent.events.length === 0 ? (
                                        <p className="text-sm text-muted-foreground">
                                            No events recorded.
                                        </p>
                                    ) : (
                                        [...consent.events]
                                            .toSorted(
                                                (a, b) =>
                                                    new Date(
                                                        b.created_at ?? 0,
                                                    ).getTime() -
                                                    new Date(
                                                        a.created_at ?? 0,
                                                    ).getTime(),
                                            )
                                            .map((event, index) => {
                                                const meta =
                                                    eventLabels[event.type] ??
                                                    eventLabels.granted;

                                                return (
                                                    <li
                                                        key={index}
                                                        className="relative"
                                                    >
                                                        <span className="absolute -left-[31px] flex size-5 items-center justify-center rounded-full border bg-background">
                                                            <meta.icon className="size-3 text-muted-foreground" />
                                                        </span>
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            <span className="text-sm font-medium">
                                                                {meta.label}
                                                            </span>
                                                            <Badge variant="outline">
                                                                {event.type}
                                                            </Badge>
                                                        </div>
                                                        <p className="mt-0.5 text-xs text-muted-foreground">
                                                            <RelativeTime
                                                                date={
                                                                    event.created_at
                                                                }
                                                            />
                                                        </p>
                                                    </li>
                                                );
                                            })
                                    )}
                                </ol>
                            </CardContent>
                        </Card>
                    </div>
                </AppSectionLayout>
            </div>
        </>
    );
}
