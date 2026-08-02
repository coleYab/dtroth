import { Head, router, setLayoutProps } from '@inertiajs/react';
import { Download, ScrollText } from 'lucide-react';
import { useState } from 'react';
import { AppPagination } from '@/components/app-pagination';
import { EmptyState } from '@/components/empty-state';
import { FilterSheet } from '@/components/filter-sheet';
import { RelativeTime } from '@/components/relative-time';
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
import { exportMethod as exportAuditLogs, index as auditLogsIndex } from '@/routes/audit-logs';
import type { AppFilter, AuditLogAction, AuditLogEntry, Paginated } from '@/types';

type Props = {
    logs: Paginated<AuditLogEntry>;
    apps: AppFilter[];
    actions: AuditLogAction[];
    filters: {
        action: string;
        app: string;
        from: string | null;
        to: string | null;
    };
};

export default function AuditLogs({ logs, apps, actions, filters }: Props) {
    setLayoutProps({
        breadcrumbs: [{ title: 'Audit logs', href: auditLogsIndex() }],
    });

    const [action, setAction] = useState(filters.action);
    const [app, setApp] = useState(filters.app);
    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');

    const hasFilters = Boolean(action || app || from || to);

    function applyFilters(): void {
        const params: Record<string, string> = {};

        if (action) {
            params.action = action;
        }

        if (app) {
            params.app = app;
        }

        if (from) {
            params.from = from;
        }

        if (to) {
            params.to = to;
        }

        router.get(auditLogsIndex().url, params, { preserveState: true });
    }

    function resetFilters(): void {
        setAction('');
        setApp('');
        setFrom('');
        setTo('');
        router.get(auditLogsIndex().url, {}, { preserveState: true });
    }

    const filterControls = (
        <>
            <div className="grid gap-2">
                <Label>Action</Label>
                <Select value={action} onValueChange={setAction}>
                    <SelectTrigger>
                        <SelectValue placeholder="All actions" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">All actions</SelectItem>
                        {actions.map((item) => (
                            <SelectItem key={item.value} value={item.value}>
                                {item.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <div className="grid gap-2">
                <Label>Application</Label>
                <Select value={app} onValueChange={setApp}>
                    <SelectTrigger>
                        <SelectValue placeholder="All applications" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">All applications</SelectItem>
                        {apps.map((item) => (
                            <SelectItem key={item.id ?? 'none'} value={item.id === null ? '' : String(item.id)}>
                                {item.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <div className="grid gap-2">
                <Label htmlFor="audit-from">From</Label>
                <Input id="audit-from" type="date" value={from} onChange={(event) => setFrom(event.target.value)} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="audit-to">To</Label>
                <Input id="audit-to" type="date" value={to} onChange={(event) => setTo(event.target.value)} />
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
            <Head title="Audit logs" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 className="text-2xl font-semibold">Audit logs</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Account-wide security trail of app, key, and webhook changes.
                        </p>
                    </div>
                    <Button asChild variant="outline" className="w-full sm:w-auto">
                        <a href={exportAuditLogs().url}>
                            <Download />
                            Export CSV
                        </a>
                    </Button>
                </div>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between gap-4">
                        <CardTitle className="text-base">Events</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4">
                        <div className="hidden items-end gap-3 md:flex">{filterControls}</div>

                        <div className="md:hidden">
                            <FilterSheet description="Filter the audit trail by action, app, and date.">
                                {filterControls}
                            </FilterSheet>
                        </div>

                        {logs.data.length === 0 ? (
                            <EmptyState
                                icon={ScrollText}
                                title="No audit events"
                                description="Security events will appear here as you manage your applications."
                            />
                        ) : (
                            <>
                                <Table className="hidden md:table">
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Timestamp</TableHead>
                                            <TableHead>Action</TableHead>
                                            <TableHead>Description</TableHead>
                                            <TableHead>Application</TableHead>
                                            <TableHead>Actor</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {logs.data.map((log) => (
                                            <TableRow key={log.id}>
                                                <TableCell>
                                                    <RelativeTime date={log.created_at} />
                                                </TableCell>
                                                <TableCell>
                                                    <code className="text-xs">{log.action}</code>
                                                </TableCell>
                                                <TableCell className="max-w-md">
                                                    {log.description}
                                                </TableCell>
                                                <TableCell>{log.app_name ?? '—'}</TableCell>
                                                <TableCell>{log.actor ?? '—'}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>

                                <div className="grid gap-3 md:hidden">
                                    {logs.data.map((log) => (
                                        <Card key={log.id}>
                                            <CardContent className="grid gap-2 p-4">
                                                <div className="flex items-center justify-between gap-3">
                                                    <code className="min-w-0 truncate text-xs">
                                                        {log.action}
                                                    </code>
                                                    <RelativeTime
                                                        date={log.created_at}
                                                        className="shrink-0 text-xs text-muted-foreground"
                                                    />
                                                </div>
                                                <p className="text-sm">{log.description}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {log.app_name ?? 'No app'}
                                                    {log.actor ? ` · ${log.actor}` : ''}
                                                </p>
                                            </CardContent>
                                        </Card>
                                    ))}
                                </div>
                            </>
                        )}

                        <AppPagination links={logs.meta.links} />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
