import { Head, router, setLayoutProps } from '@inertiajs/react';
import {
    ExternalLink,
    MoreHorizontal,
    Plus,
    ScrollText,
    Trash2,
    Webhook as WebhookIcon,
} from 'lucide-react';
import { useState } from 'react';
import {
    AppSectionHeader,
    AppSectionLayout,
    appSectionNavItems,
} from '@/components/app-section-layout';
import { EmptyState } from '@/components/empty-state';
import { RelativeTime } from '@/components/relative-time';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { WebhookSecretRevealDialog } from '@/components/webhook-secret-reveal-dialog';
import { WebhookStatusBadge } from '@/components/webhook-status';
import { shortAppName } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as appsIndex } from '@/routes/apps';
import {
    create as createWebhook,
    destroy,
    index as webhooksIndex,
    logs as webhookLogs,
} from '@/routes/apps/webhooks';
import type {
    DeveloperApp,
    RevealedSecret,
    Webhook,
    WebhookEvent,
} from '@/types';

type Props = {
    app: DeveloperApp;
    webhooks: { data: Webhook[] };
    availableEvents: WebhookEvent[];
    revealedSecret: RevealedSecret | null;
};

export default function Webhooks({
    app,
    webhooks,
    availableEvents,
    revealedSecret,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Applications', href: appsIndex() },
            { title: shortAppName(app.name), href: `/apps/${app.id}` },
        ],
    });

    const [deleteTarget, setDeleteTarget] = useState<Webhook | null>(null);
    const [deleting, setDeleting] = useState(false);

    function deleteWebhook(): void {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);

        router.delete(destroy({ app: app.id, webhook: deleteTarget.id }).url, {
            onFinish: () => setDeleting(false),
        });
    }

    return (
        <>
            <Head title={`${app.name} · Webhooks`} />

            <div className="flex flex-col gap-6">
                <AppSectionHeader
                    app={app}
                    description="Deliver verification events to your servers over signed POST requests."
                    actions={
                        <Button asChild className="w-full sm:w-auto">
                            <a href={createWebhook(app.id).url}>
                                <Plus />
                                Add Webhook
                            </a>
                        </Button>
                    }
                />

                <AppSectionLayout
                    items={appSectionNavItems(app)}
                    active="Webhooks"
                    label="Application"
                >
                    {webhooks.data.length === 0 ? (
                        <EmptyState
                            icon={WebhookIcon}
                            title="No webhooks yet"
                            description="Subscribe to verification events and receive signed POST deliveries to your endpoint."
                            action={
                                <Button asChild>
                                    <a href={createWebhook(app.id).url}>
                                        <Plus />
                                        Add Webhook
                                    </a>
                                </Button>
                            }
                        />
                    ) : (
                        <>
                            <Card className="hidden md:block">
                                <CardHeader>
                                    <CardTitle>Endpoints</CardTitle>
                                    <CardDescription>
                                        {webhooks.data.length} endpoint
                                        {webhooks.data.length === 1 ? '' : 's'}{' '}
                                        for {availableEvents.length} event
                                        types.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="p-0">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Endpoint</TableHead>
                                                <TableHead>Events</TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead>
                                                    Last triggered
                                                </TableHead>
                                                <TableHead className="w-12" />
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {webhooks.data.map((webhook) => (
                                                <TableRow key={webhook.id}>
                                                    <TableCell>
                                                        <TooltipProvider>
                                                            <Tooltip>
                                                                <TooltipTrigger
                                                                    asChild
                                                                >
                                                                    <a
                                                                        href={
                                                                            webhookLogs(
                                                                                {
                                                                                    app: app.id,
                                                                                    webhook:
                                                                                        webhook.id,
                                                                                },
                                                                            )
                                                                                .url
                                                                        }
                                                                        className="block max-w-64 truncate font-mono text-xs text-primary hover:underline"
                                                                    >
                                                                        {
                                                                            webhook.url
                                                                        }
                                                                    </a>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    {
                                                                        webhook.url
                                                                    }
                                                                </TooltipContent>
                                                            </Tooltip>
                                                        </TooltipProvider>
                                                        {webhook.description && (
                                                            <p className="mt-0.5 truncate text-xs text-muted-foreground">
                                                                {
                                                                    webhook.description
                                                                }
                                                            </p>
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex max-w-56 flex-wrap gap-1">
                                                            {webhook.events.map(
                                                                (event) => (
                                                                    <Badge
                                                                        key={
                                                                            event.name
                                                                        }
                                                                        variant="outline"
                                                                    >
                                                                        {
                                                                            event.label
                                                                        }
                                                                    </Badge>
                                                                ),
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <WebhookStatusBadge
                                                            isFailing={
                                                                webhook.is_failing
                                                            }
                                                        />
                                                    </TableCell>
                                                    <TableCell>
                                                        <RelativeTime
                                                            date={
                                                                webhook.last_triggered_at
                                                            }
                                                        />
                                                    </TableCell>
                                                    <TableCell>
                                                        <DropdownMenu>
                                                            <DropdownMenuTrigger
                                                                asChild
                                                            >
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    aria-label="Webhook actions"
                                                                >
                                                                    <MoreHorizontal />
                                                                </Button>
                                                            </DropdownMenuTrigger>
                                                            <DropdownMenuContent align="end">
                                                                <DropdownMenuItem
                                                                    asChild
                                                                >
                                                                    <a
                                                                        href={
                                                                            webhookLogs(
                                                                                {
                                                                                    app: app.id,
                                                                                    webhook:
                                                                                        webhook.id,
                                                                                },
                                                                            )
                                                                                .url
                                                                        }
                                                                    >
                                                                        <ScrollText />
                                                                        View
                                                                        logs
                                                                    </a>
                                                                </DropdownMenuItem>
                                                                <DropdownMenuItem
                                                                    variant="destructive"
                                                                    onClick={() =>
                                                                        setDeleteTarget(
                                                                            webhook,
                                                                        )
                                                                    }
                                                                >
                                                                    <Trash2 />
                                                                    Delete
                                                                </DropdownMenuItem>
                                                            </DropdownMenuContent>
                                                        </DropdownMenu>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </CardContent>
                            </Card>

                            <div className="grid gap-4 md:hidden">
                                {webhooks.data.map((webhook) => (
                                    <Card key={webhook.id}>
                                        <CardContent className="grid gap-3 p-4">
                                            <div className="flex items-start justify-between gap-3">
                                                <code className="min-w-0 flex-1 truncate font-mono text-xs">
                                                    {webhook.url}
                                                </code>
                                                <WebhookStatusBadge
                                                    isFailing={
                                                        webhook.is_failing
                                                    }
                                                />
                                            </div>
                                            {webhook.description && (
                                                <p className="text-xs text-muted-foreground">
                                                    {webhook.description}
                                                </p>
                                            )}
                                            <div className="flex flex-wrap gap-1">
                                                {webhook.events.map((event) => (
                                                    <Badge
                                                        key={event.name}
                                                        variant="outline"
                                                    >
                                                        {event.label}
                                                    </Badge>
                                                ))}
                                            </div>
                                            <p className="text-xs text-muted-foreground">
                                                Last triggered:{' '}
                                                <RelativeTime
                                                    date={
                                                        webhook.last_triggered_at
                                                    }
                                                />
                                            </p>
                                            <div className="flex items-center gap-2">
                                                <Button
                                                    asChild
                                                    size="sm"
                                                    className="flex-1"
                                                >
                                                    <a
                                                        href={
                                                            webhookLogs({
                                                                app: app.id,
                                                                webhook:
                                                                    webhook.id,
                                                            }).url
                                                        }
                                                    >
                                                        <ExternalLink />
                                                        View Logs
                                                    </a>
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="icon"
                                                    onClick={() =>
                                                        setDeleteTarget(webhook)
                                                    }
                                                    aria-label={`Delete webhook ${webhook.id}`}
                                                >
                                                    <Trash2 />
                                                </Button>
                                            </div>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        </>
                    )}

                    <WebhookSecretRevealDialog
                        open={Boolean(revealedSecret)}
                        onOpenChange={(open) => {
                            if (!open) {
                                router.visit(webhooksIndex(app.id).url, {
                                    preserveState: true,
                                    replace: true,
                                });
                            }
                        }}
                        revealedSecret={
                            revealedSecret ?? { id: 0, url: '', secret: '' }
                        }
                        isRegenerated={false}
                    />
                </AppSectionLayout>

                <Dialog
                    open={deleteTarget !== null}
                    onOpenChange={(open) =>
                        open ? undefined : setDeleteTarget(null)
                    }
                >
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Delete webhook</DialogTitle>
                            <DialogDescription>
                                This endpoint will stop receiving deliveries
                                immediately. This action cannot be undone.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => setDeleteTarget(null)}
                                disabled={deleting}
                            >
                                Cancel
                            </Button>
                            <Button
                                variant="destructive"
                                onClick={deleteWebhook}
                                disabled={deleting}
                            >
                                <Trash2 />
                                Delete webhook
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}
