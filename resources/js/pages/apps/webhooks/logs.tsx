import { Head, router, setLayoutProps } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import { useState } from 'react';
import { AppPagination } from '@/components/app-pagination';
import {
    AppSectionHeader,
    AppSectionLayout,
    appSectionNavItems,
} from '@/components/app-section-layout';
import { FilterSheet } from '@/components/filter-sheet';
import { PayloadViewDialog } from '@/components/payload-view-dialog';
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
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { WebhookSecretRevealDialog } from '@/components/webhook-secret-reveal-dialog';
import {
    DeliveryStatusBadge,
    EndpointCard,
    RecentFailureAlert,
} from '@/components/webhook-status';
import { shortAppName } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as appsIndex } from '@/routes/apps';
import {
    logs as webhooksIndex,
    regenerateSecret,
    retryDelivery,
} from '@/routes/apps/webhooks';
import type {
    DeveloperApp,
    Paginated,
    RevealedSecret,
    Webhook,
    WebhookDelivery,
} from '@/types';

type Props = {
    app: DeveloperApp;
    webhook: Webhook;
    deliveries: Paginated<WebhookDelivery>;
    filters: {
        status: string;
        from: string | null;
        to: string | null;
    };
    revealedSecret: RevealedSecret | null;
    permissions: string[];
};

export default function WebhookLogs({
    app,
    webhook,
    deliveries,
    filters,
    revealedSecret,
    permissions,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Applications', href: appsIndex() },
            { title: shortAppName(app.name), href: `/apps/${app.id}` },
        ],
    });

    const [status, setStatus] = useState(filters.status);
    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');
    const [retrying, setRetrying] = useState<number | null>(null);

    const canManageWebhooks = permissions.includes('manage_webhooks');
    const [payloadTarget, setPayloadTarget] = useState<WebhookDelivery | null>(
        null,
    );

    function applyFilters(): void {
        const params: Record<string, string> = {};

        if (status) {
            params.status = status;
        }

        if (from) {
            params.from = from;
        }

        if (to) {
            params.to = to;
        }

        router.get(
            webhooksIndex({ app: app.id, webhook: webhook.id }).url,
            params,
            {
                preserveState: true,
            },
        );
    }

    function resetFilters(): void {
        setStatus('');
        setFrom('');
        setTo('');
        router.get(
            webhooksIndex({ app: app.id, webhook: webhook.id }).url,
            {},
            {
                preserveState: true,
            },
        );
    }

    function retry(delivery: WebhookDelivery): void {
        setRetrying(delivery.id);

        router.post(
            retryDelivery({
                app: app.id,
                webhook: webhook.id,
                delivery: delivery.id,
            }).url,
            {},
            {
                preserveState: true,
                onFinish: () => setRetrying(null),
            },
        );
    }

    const statusFilters = [
        { value: '', label: 'All statuses' },
        { value: 'success', label: 'Success' },
        { value: 'failed', label: 'Failed' },
        { value: 'retrying', label: 'Retrying' },
        { value: 'pending', label: 'Pending' },
    ];

    return (
        <>
            <Head title={`${app.name} · Webhook logs`} />

            <div className="flex flex-col gap-6">
                <AppSectionHeader
                    app={app}
                    description="Delivery history for this endpoint."
                />

                <AppSectionLayout
                    items={appSectionNavItems(app)}
                    active="Webhooks"
                    label="Application"
                >
                    <div className="grid gap-6">
                        <EndpointCard
                            url={webhook.url}
                            events={webhook.events}
                            secretPreview={webhook.secret_preview}
                            lastTriggeredAt={webhook.last_triggered_at}
                            onRegenerate={() =>
                                router.post(
                                    regenerateSecret({
                                        app: app.id,
                                        webhook: webhook.id,
                                    }).url,
                                )
                            }
                        />

                        <RecentFailureAlert
                            count={webhook.recent_failure_count}
                        />

                        <Card>
                            <CardHeader>
                                <CardTitle>Deliveries</CardTitle>
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
                                                {statusFilters.map((filter) => (
                                                    <SelectItem
                                                        key={filter.value}
                                                        value={filter.value}
                                                    >
                                                        {filter.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="delivery-from">
                                            From
                                        </Label>
                                        <Input
                                            id="delivery-from"
                                            type="date"
                                            value={from}
                                            onChange={(event) =>
                                                setFrom(event.target.value)
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="delivery-to">To</Label>
                                        <Input
                                            id="delivery-to"
                                            type="date"
                                            value={to}
                                            onChange={(event) =>
                                                setTo(event.target.value)
                                            }
                                        />
                                    </div>
                                    <Button onClick={applyFilters}>
                                        Apply
                                    </Button>
                                    {(status || from || to) && (
                                        <Button
                                            variant="ghost"
                                            onClick={resetFilters}
                                        >
                                            Reset
                                        </Button>
                                    )}
                                </div>

                                <div className="md:hidden">
                                    <FilterSheet description="Filter deliveries by status and date.">
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
                                                    {statusFilters.map(
                                                        (filter) => (
                                                            <SelectItem
                                                                key={
                                                                    filter.value
                                                                }
                                                                value={
                                                                    filter.value
                                                                }
                                                            >
                                                                {filter.label}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="delivery-from-mobile">
                                                From
                                            </Label>
                                            <Input
                                                id="delivery-from-mobile"
                                                type="date"
                                                value={from}
                                                onChange={(event) =>
                                                    setFrom(event.target.value)
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="delivery-to-mobile">
                                                To
                                            </Label>
                                            <Input
                                                id="delivery-to-mobile"
                                                type="date"
                                                value={to}
                                                onChange={(event) =>
                                                    setTo(event.target.value)
                                                }
                                            />
                                        </div>
                                        <Button onClick={applyFilters}>
                                            Apply
                                        </Button>
                                        {(status || from || to) && (
                                            <Button
                                                variant="ghost"
                                                onClick={resetFilters}
                                            >
                                                Reset
                                            </Button>
                                        )}
                                    </FilterSheet>
                                </div>

                                {deliveries.data.length === 0 ? (
                                    <p className="text-muted-foreground py-10 text-center text-sm">
                                        No deliveries match these filters.
                                    </p>
                                ) : (
                                    <>
                                        <Table className="hidden md:table">
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Event</TableHead>
                                                    <TableHead>
                                                        Status
                                                    </TableHead>
                                                    <TableHead>
                                                        Attempts
                                                    </TableHead>
                                                    <TableHead>
                                                        Response
                                                    </TableHead>
                                                    <TableHead>
                                                        Created
                                                    </TableHead>
                                                    <TableHead className="w-28" />
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {deliveries.data.map(
                                                    (delivery) => (
                                                        <TableRow
                                                            key={delivery.id}
                                                        >
                                                            <TableCell className="font-mono text-xs">
                                                                {delivery.event}
                                                            </TableCell>
                                                            <TableCell>
                                                                <DeliveryStatusBadge
                                                                    status={
                                                                        delivery.status
                                                                    }
                                                                />
                                                            </TableCell>
                                                            <TableCell className="tabular-nums">
                                                                {
                                                                    delivery.attempts
                                                                }
                                                            </TableCell>
                                                            <TableCell>
                                                                {delivery.response_status ??
                                                                    '—'}
                                                            </TableCell>
                                                            <TableCell>
                                                                <RelativeTime
                                                                    date={
                                                                        delivery.created_at
                                                                    }
                                                                />
                                                            </TableCell>
                                                            <TableCell>
                                                                <div className="flex items-center gap-1">
                                                                    <Button
                                                                        variant="outline"
                                                                        size="sm"
                                                                        onClick={() =>
                                                                            setPayloadTarget(
                                                                                delivery,
                                                                            )
                                                                        }
                                                                    >
                                                                        Payload
                                                                    </Button>
                                                                    {delivery.status ===
                                                                        'failed' &&
                                                                        canManageWebhooks && (
                                                                            <Button
                                                                                variant="outline"
                                                                                size="icon"
                                                                                onClick={() =>
                                                                                    retry(
                                                                                        delivery,
                                                                                    )
                                                                                }
                                                                                disabled={
                                                                                    retrying ===
                                                                                    delivery.id
                                                                                }
                                                                                aria-label={`Retry delivery ${delivery.id}`}
                                                                            >
                                                                                {retrying ===
                                                                                delivery.id ? (
                                                                                    <Spinner className="size-4" />
                                                                                ) : (
                                                                                    <RefreshCw />
                                                                                )}
                                                                            </Button>
                                                                        )}
                                                                </div>
                                                            </TableCell>
                                                        </TableRow>
                                                    ),
                                                )}
                                            </TableBody>
                                        </Table>

                                        <div className="grid gap-3 md:hidden">
                                            {deliveries.data.map((delivery) => (
                                                <Card key={delivery.id}>
                                                    <CardContent className="grid gap-2 p-4">
                                                        <div className="flex items-center justify-between gap-3">
                                                            <code className="min-w-0 truncate font-mono text-xs">
                                                                {delivery.event}
                                                            </code>
                                                            <DeliveryStatusBadge
                                                                status={
                                                                    delivery.status
                                                                }
                                                            />
                                                        </div>
                                                        <div className="text-muted-foreground flex flex-wrap gap-x-4 gap-y-1 text-xs">
                                                            <span>
                                                                Attempts:{' '}
                                                                <span className="tabular-nums">
                                                                    {
                                                                        delivery.attempts
                                                                    }
                                                                </span>
                                                            </span>
                                                            <span>
                                                                Response:{' '}
                                                                <span className="tabular-nums">
                                                                    {delivery.response_status ??
                                                                        '—'}
                                                                </span>
                                                            </span>
                                                            <span>
                                                                <RelativeTime
                                                                    date={
                                                                        delivery.created_at
                                                                    }
                                                                />
                                                            </span>
                                                        </div>
                                                        <div className="flex gap-2">
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                className="flex-1"
                                                                onClick={() =>
                                                                    setPayloadTarget(
                                                                        delivery,
                                                                    )
                                                                }
                                                            >
                                                                View payload
                                                            </Button>
                                                            {delivery.status ===
                                                                'failed' &&
                                                                canManageWebhooks && (
                                                                    <Button
                                                                        variant="outline"
                                                                        size="sm"
                                                                        onClick={() =>
                                                                            retry(
                                                                                delivery,
                                                                            )
                                                                        }
                                                                        disabled={
                                                                            retrying ===
                                                                            delivery.id
                                                                        }
                                                                        className="flex-1"
                                                                    >
                                                                        {retrying ===
                                                                        delivery.id ? (
                                                                            <Spinner className="size-4" />
                                                                        ) : (
                                                                            <RefreshCw />
                                                                        )}
                                                                        Retry
                                                                        now
                                                                    </Button>
                                                                )}
                                                        </div>
                                                    </CardContent>
                                                </Card>
                                            ))}
                                        </div>
                                    </>
                                )}

                                <AppPagination links={deliveries.meta.links} />
                            </CardContent>
                        </Card>
                    </div>

                    <WebhookSecretRevealDialog
                        open={Boolean(revealedSecret)}
                        onOpenChange={(open) => {
                            if (!open) {
                                router.visit(
                                    webhooksIndex({
                                        app: app.id,
                                        webhook: webhook.id,
                                    }).url,
                                    {
                                        preserveState: true,
                                        replace: true,
                                    },
                                );
                            }
                        }}
                        revealedSecret={
                            revealedSecret ?? { id: 0, url: '', secret: '' }
                        }
                        isRegenerated
                    />

                    <PayloadViewDialog
                        open={payloadTarget !== null}
                        onOpenChange={(open) =>
                            open ? undefined : setPayloadTarget(null)
                        }
                        title={`Delivery payload · ${payloadTarget?.event ?? ''}`}
                        payload={payloadTarget?.payload ?? null}
                        response={{
                            status: payloadTarget?.response_status ?? null,
                            body: payloadTarget?.response_body ?? null,
                        }}
                    />
                </AppSectionLayout>
            </div>
        </>
    );
}
