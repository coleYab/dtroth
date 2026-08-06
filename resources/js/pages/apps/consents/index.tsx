import { Head, router, setLayoutProps } from '@inertiajs/react';
import { FileCheck } from 'lucide-react';
import { useState } from 'react';
import { AppPagination } from '@/components/app-pagination';
import {
    AppSectionHeader,
    AppSectionLayout,
    appSectionNavItems,
} from '@/components/app-section-layout';
import { ConsentStatusBadge } from '@/components/consent-status-badge';
import { EmptyState } from '@/components/empty-state';
import { FilterSheet } from '@/components/filter-sheet';
import { RelativeTime } from '@/components/relative-time';
import { ResultBadge } from '@/components/result-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { shortId } from '@/lib/utils';
import { shortAppName } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as appsIndex } from '@/routes/apps';
import {
    index as consentsIndex,
    show as consentShow,
} from '@/routes/apps/consents';
import type { Consent, DeveloperApp, Paginated } from '@/types';

type Props = {
    app: DeveloperApp;
    consents: Paginated<Consent>;
    counts: {
        active: number;
        revoked: number;
        expired: number;
    };
    filters: {
        status: string;
        query: string;
    };
};

export default function Consents({ app, consents, counts, filters }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Applications', href: appsIndex() },
            { title: shortAppName(app.name), href: `/apps/${app.id}` },
        ],
    });

    const [status, setStatus] = useState(filters.status);
    const [query, setQuery] = useState(filters.query);

    function applyFilters(): void {
        const params: Record<string, string> = {};

        if (status) {
            params.status = status;
        }

        if (query) {
            params.query = query;
        }

        router.get(consentsIndex(app.id).url, params, { preserveState: true });
    }

    function resetFilters(): void {
        setStatus('');
        setQuery('');
        router.get(consentsIndex(app.id).url, {}, { preserveState: true });
    }

    const summary = [
        { label: 'Active', value: counts.active, className: 'text-foreground' },
        {
            label: 'Revoked',
            value: counts.revoked,
            className: 'text-destructive',
        },
        {
            label: 'Expired',
            value: counts.expired,
            className: 'text-muted-foreground',
        },
    ];

    return (
        <>
            <Head title={`${app.name} · Consents`} />

            <div className="flex flex-col gap-6">
                <AppSectionHeader
                    app={app}
                    description="Anonymized consent records for age verifications."
                />

                <AppSectionLayout
                    items={appSectionNavItems(app)}
                    active="Consents"
                    label="Application"
                >
                    <div className="grid gap-6">
                        <Card>
                            <CardContent className="grid grid-cols-3 gap-4 p-4">
                                {summary.map((item) => (
                                    <div
                                        key={item.label}
                                        className="text-center"
                                    >
                                        <p
                                            className={`text-2xl font-semibold tabular-nums ${item.className}`}
                                        >
                                            {item.value}
                                        </p>
                                        <p className="mt-0.5 text-xs text-muted-foreground">
                                            {item.label}
                                        </p>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Consents</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4">
                                <div className="hidden items-end gap-3 md:flex">
                                    <div className="grid gap-1.5">
                                        <Label>Status</Label>
                                        <Select
                                            value={status}
                                            onValueChange={setStatus}
                                        >
                                            <SelectTrigger className="w-44">
                                                <SelectValue placeholder="All statuses" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="">
                                                    All statuses
                                                </SelectItem>
                                                <SelectItem value="active">
                                                    Active
                                                </SelectItem>
                                                <SelectItem value="revoked">
                                                    Revoked
                                                </SelectItem>
                                                <SelectItem value="expired">
                                                    Expired
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="consent-query">
                                            Search
                                        </Label>
                                        <Input
                                            id="consent-query"
                                            value={query}
                                            onChange={(event) =>
                                                setQuery(event.target.value)
                                            }
                                            placeholder="Identifier or age query"
                                            className="w-64"
                                        />
                                    </div>
                                    <Button onClick={applyFilters}>
                                        Apply
                                    </Button>
                                    {(status || query) && (
                                        <Button
                                            variant="ghost"
                                            onClick={resetFilters}
                                        >
                                            Reset
                                        </Button>
                                    )}
                                </div>

                                <div className="md:hidden">
                                    <FilterSheet description="Filter consents by status and identifier.">
                                        <div className="grid gap-2">
                                            <Label>Status</Label>
                                            <Select
                                                value={status}
                                                onValueChange={setStatus}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="All statuses" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="">
                                                        All statuses
                                                    </SelectItem>
                                                    <SelectItem value="active">
                                                        Active
                                                    </SelectItem>
                                                    <SelectItem value="revoked">
                                                        Revoked
                                                    </SelectItem>
                                                    <SelectItem value="expired">
                                                        Expired
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="consent-query-mobile">
                                                Search
                                            </Label>
                                            <Input
                                                id="consent-query-mobile"
                                                value={query}
                                                onChange={(event) =>
                                                    setQuery(event.target.value)
                                                }
                                                placeholder="Identifier or age query"
                                            />
                                        </div>
                                        <Button onClick={applyFilters}>
                                            Apply
                                        </Button>
                                        {(status || query) && (
                                            <Button
                                                variant="ghost"
                                                onClick={resetFilters}
                                            >
                                                Reset
                                            </Button>
                                        )}
                                    </FilterSheet>
                                </div>

                                {consents.data.length === 0 ? (
                                    <EmptyState
                                        icon={FileCheck}
                                        title="No consents found"
                                        description="Consents appear here once an end user completes an age verification."
                                    />
                                ) : (
                                    <>
                                        <Table className="hidden md:table">
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>
                                                        User identifier
                                                    </TableHead>
                                                    <TableHead>
                                                        Age-query sent
                                                    </TableHead>
                                                    <TableHead>
                                                        Result
                                                    </TableHead>
                                                    <TableHead>
                                                        Status
                                                    </TableHead>
                                                    <TableHead>
                                                        Created
                                                    </TableHead>
                                                    <TableHead>
                                                        Expires
                                                    </TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {consents.data.map(
                                                    (consent) => (
                                                        <TableRow
                                                            key={consent.id}
                                                        >
                                                            <TableCell>
                                                                <a
                                                                    href={
                                                                        consentShow(
                                                                            {
                                                                                app: app.id,
                                                                                consent:
                                                                                    consent.id,
                                                                            },
                                                                        ).url
                                                                    }
                                                                    className="font-mono text-xs text-primary hover:underline"
                                                                >
                                                                    {shortId(
                                                                        consent.subject_identifier,
                                                                    )}
                                                                </a>
                                                            </TableCell>
                                                            <TableCell>
                                                                {consent.age_query ??
                                                                    '—'}
                                                            </TableCell>
                                                            <TableCell>
                                                                <ResultBadge
                                                                    result={
                                                                        consent.result
                                                                    }
                                                                />
                                                            </TableCell>
                                                            <TableCell>
                                                                <ConsentStatusBadge
                                                                    status={
                                                                        consent.status
                                                                    }
                                                                />
                                                            </TableCell>
                                                            <TableCell>
                                                                <RelativeTime
                                                                    date={
                                                                        consent.created_at
                                                                    }
                                                                />
                                                            </TableCell>
                                                            <TableCell>
                                                                {consent.expires_at
                                                                    ? new Date(
                                                                          consent.expires_at,
                                                                      ).toLocaleDateString()
                                                                    : '—'}
                                                            </TableCell>
                                                        </TableRow>
                                                    ),
                                                )}
                                            </TableBody>
                                        </Table>

                                        <div className="grid gap-3 md:hidden">
                                            {consents.data.map((consent) => (
                                                <Card key={consent.id}>
                                                    <CardContent className="grid gap-2 p-4">
                                                        <div className="flex items-center justify-between gap-3">
                                                            <code className="min-w-0 truncate font-mono text-xs">
                                                                {shortId(
                                                                    consent.subject_identifier,
                                                                )}
                                                            </code>
                                                            <ConsentStatusBadge
                                                                status={
                                                                    consent.status
                                                                }
                                                            />
                                                        </div>
                                                        <p className="text-sm">
                                                            {consent.age_query ??
                                                                'No age query'}
                                                            {' · '}
                                                            <span
                                                                className={
                                                                    consent.result
                                                                        ? 'text-primary'
                                                                        : 'text-destructive'
                                                                }
                                                            >
                                                                {consent.result ===
                                                                null
                                                                    ? '—'
                                                                    : consent.result
                                                                      ? 'True'
                                                                      : 'False'}
                                                            </span>
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            <RelativeTime
                                                                date={
                                                                    consent.created_at
                                                                }
                                                            />
                                                            {consent.expires_at
                                                                ? ` · expires ${new Date(consent.expires_at).toLocaleDateString()}`
                                                                : ''}
                                                        </p>
                                                        <Button
                                                            asChild
                                                            variant="outline"
                                                            size="sm"
                                                            className="justify-start"
                                                        >
                                                            <a
                                                                href={
                                                                    consentShow(
                                                                        {
                                                                            app: app.id,
                                                                            consent:
                                                                                consent.id,
                                                                        },
                                                                    ).url
                                                                }
                                                            >
                                                                View details
                                                            </a>
                                                        </Button>
                                                    </CardContent>
                                                </Card>
                                            ))}
                                        </div>
                                    </>
                                )}

                                <AppPagination links={consents.meta.links} />
                            </CardContent>
                        </Card>
                    </div>
                </AppSectionLayout>
            </div>
        </>
    );
}
