import { Head, router, setLayoutProps } from '@inertiajs/react';
import { Download, Terminal } from 'lucide-react';
import { useState } from 'react';
import { AppPagination } from '@/components/app-pagination';
import {
    AppSectionHeader,
    AppSectionLayout,
    appSectionNavItems,
} from '@/components/app-section-layout';
import { EmptyState } from '@/components/empty-state';
import { FilterSheet } from '@/components/filter-sheet';
import { PayloadViewDialog } from '@/components/payload-view-dialog';
import { RelativeTime } from '@/components/relative-time';
import { ResultBadge } from '@/components/result-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import { shortAppName } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as appsIndex, logs as logsRoute } from '@/routes/apps';
import { exportMethod as exportLogs } from '@/routes/apps/logs';
import type { ApiCallLog, DeveloperApp, Paginated } from '@/types';

type Props = {
    app: DeveloperApp;
    calls: Paginated<ApiCallLog>;
    filters: {
        query_type: string;
        status: string;
        from: string | null;
        to: string | null;
    };
};

export default function Logs({ app, calls, filters }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Applications', href: appsIndex() },
            { title: shortAppName(app.name), href: `/apps/${app.id}` },
        ],
    });

    const [queryType, setQueryType] = useState(filters.query_type);
    const [status, setStatus] = useState(filters.status);
    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');
    const [payloadTarget, setPayloadTarget] = useState<ApiCallLog | null>(null);

    const hasFilters = Boolean(queryType || status || from || to);

    function applyFilters(): void {
        const params: Record<string, string> = {};

        if (queryType) {
            params.query_type = queryType;
        }

        if (status) {
            params.status = status;
        }

        if (from) {
            params.from = from;
        }

        if (to) {
            params.to = to;
        }

        router.get(logsRoute(app.id).url, params, { preserveState: true });
    }

    function resetFilters(): void {
        setQueryType('');
        setStatus('');
        setFrom('');
        setTo('');
        router.get(logsRoute(app.id).url, {}, { preserveState: true });
    }

    const filterControls = (
        <>
            <div className="grid gap-2">
                <Label>Query type</Label>
                <Select value={queryType} onValueChange={setQueryType}>
                    <SelectTrigger>
                        <SelectValue placeholder="All query types" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">All query types</SelectItem>
                        <SelectItem value="equal_above">
                            Equal or above
                        </SelectItem>
                        <SelectItem value="equal_below">
                            Equal or below
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div className="grid gap-2">
                <Label>Status</Label>
                <Select value={status} onValueChange={setStatus}>
                    <SelectTrigger>
                        <SelectValue placeholder="All statuses" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">All statuses</SelectItem>
                        <SelectItem value="success">Success (2xx)</SelectItem>
                        <SelectItem value="failed">Failed (4xx/5xx)</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div className="grid gap-2">
                <Label htmlFor="logs-from">From</Label>
                <Input
                    id="logs-from"
                    type="date"
                    value={from}
                    onChange={(event) => setFrom(event.target.value)}
                />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="logs-to">To</Label>
                <Input
                    id="logs-to"
                    type="date"
                    value={to}
                    onChange={(event) => setTo(event.target.value)}
                />
            </div>
            <Button onClick={applyFilters}>Apply</Button>
            {hasFilters && (
                <Button variant="ghost" onClick={resetFilters}>
                    Reset
                </Button>
            )}
        </>
    );

    return (
        <>
            <Head title={`${app.name} · Logs`} />

            <div className="flex flex-col gap-6">
                <AppSectionHeader
                    app={app}
                    description="Raw API call log for this application, newest first."
                />

                <AppSectionLayout
                    items={appSectionNavItems(app)}
                    active="Logs"
                    label="Application"
                >
                    <Card>
                        <CardHeader className="flex flex-row items-start justify-between gap-4 space-y-0">
                            <div className="grid gap-1.5">
                                <CardTitle>API calls</CardTitle>
                                <CardDescription>
                                    {calls.meta.total.toLocaleString()} call
                                    {calls.meta.total === 1 ? '' : 's'}{' '}
                                    recorded.
                                </CardDescription>
                            </div>
                            <Button asChild variant="outline">
                                <a href={exportLogs(app.id).url}>
                                    <Download />
                                    Export CSV
                                </a>
                            </Button>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <div className="hidden items-end gap-3 md:flex">
                                {filterControls}
                            </div>

                            <div className="md:hidden">
                                <FilterSheet description="Filter API calls by query type, status, and date.">
                                    {filterControls}
                                </FilterSheet>
                            </div>

                            {calls.data.length === 0 ? (
                                <EmptyState
                                    icon={Terminal}
                                    title="No API calls"
                                    description="API calls will appear here once your integration starts verifying ages."
                                />
                            ) : (
                                <>
                                    <Table className="hidden md:table">
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Timestamp</TableHead>
                                                <TableHead>Query</TableHead>
                                                <TableHead>Result</TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead>
                                                    Response time
                                                </TableHead>
                                                <TableHead />
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {calls.data.map((call) => (
                                                <TableRow key={call.id}>
                                                    <TableCell>
                                                        <RelativeTime
                                                            date={
                                                                call.created_at
                                                            }
                                                        />
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center gap-2">
                                                            <code className="text-xs">
                                                                {call.age_query ??
                                                                    '—'}
                                                            </code>
                                                            <span className="text-muted-foreground text-xs">
                                                                {call.method}{' '}
                                                                {call.path}
                                                            </span>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <ResultBadge
                                                            result={call.result}
                                                        />
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge
                                                            variant={
                                                                call.status_code >=
                                                                    200 &&
                                                                call.status_code <
                                                                    300
                                                                    ? 'default'
                                                                    : 'destructive'
                                                            }
                                                        >
                                                            {call.status_code}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="tabular-nums">
                                                        {call.response_time_ms !==
                                                        null
                                                            ? `${call.response_time_ms}ms`
                                                            : '—'}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                setPayloadTarget(
                                                                    call,
                                                                )
                                                            }
                                                        >
                                                            Details
                                                        </Button>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>

                                    <div className="grid gap-3 md:hidden">
                                        {calls.data.map((call) => (
                                            <Card key={call.id}>
                                                <CardContent className="grid gap-2 p-4">
                                                    <div className="flex items-center justify-between gap-3">
                                                        <code className="text-xs">
                                                            {call.age_query ??
                                                                '—'}
                                                        </code>
                                                        <Badge
                                                            variant={
                                                                call.status_code >=
                                                                    200 &&
                                                                call.status_code <
                                                                    300
                                                                    ? 'default'
                                                                    : 'destructive'
                                                            }
                                                        >
                                                            {call.status_code}
                                                        </Badge>
                                                    </div>
                                                    <p className="text-muted-foreground text-xs">
                                                        {call.method}{' '}
                                                        {call.path}
                                                    </p>
                                                    <div className="text-muted-foreground flex flex-wrap gap-x-4 gap-y-1 text-xs">
                                                        <span className="flex items-center gap-1">
                                                            Result:
                                                            <ResultBadge
                                                                result={
                                                                    call.result
                                                                }
                                                            />
                                                        </span>
                                                        <span>
                                                            {call.response_time_ms !==
                                                            null
                                                                ? `${call.response_time_ms}ms`
                                                                : '—'}
                                                        </span>
                                                        <RelativeTime
                                                            date={
                                                                call.created_at
                                                            }
                                                        />
                                                    </div>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        className="justify-start"
                                                        onClick={() =>
                                                            setPayloadTarget(
                                                                call,
                                                            )
                                                        }
                                                    >
                                                        View details
                                                    </Button>
                                                </CardContent>
                                            </Card>
                                        ))}
                                    </div>
                                </>
                            )}

                            <AppPagination links={calls.meta.links} />
                        </CardContent>
                    </Card>

                    <PayloadViewDialog
                        open={payloadTarget !== null}
                        onOpenChange={(open) =>
                            open ? undefined : setPayloadTarget(null)
                        }
                        title={`API call · ${payloadTarget?.method ?? ''} ${payloadTarget?.path ?? ''}`}
                        payload={payloadTarget?.request_payload ?? null}
                        response={{
                            status: payloadTarget?.status_code ?? null,
                            body: payloadTarget?.response_payload
                                ? JSON.stringify(payloadTarget.response_payload)
                                : null,
                        }}
                    />
                </AppSectionLayout>
            </div>
        </>
    );
}
