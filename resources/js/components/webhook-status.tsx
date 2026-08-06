import { Loader2, TriangleAlert } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type DeliveryStatusBadgeProps = {
    status: 'pending' | 'retrying' | 'success' | 'failed';
};

export function DeliveryStatusBadge({ status }: DeliveryStatusBadgeProps) {
    if (status === 'success') {
        return <Badge variant="default">Success</Badge>;
    }

    if (status === 'failed') {
        return <Badge variant="destructive">Failed</Badge>;
    }

    if (status === 'retrying') {
        return (
            <Badge variant="secondary" className="gap-1">
                <Loader2 className="size-3 animate-spin" />
                Retrying
            </Badge>
        );
    }

    return <Badge variant="outline">Pending</Badge>;
}

type WebhookStatusBadgeProps = {
    isFailing: boolean;
};

export function WebhookStatusBadge({ isFailing }: WebhookStatusBadgeProps) {
    if (isFailing) {
        return (
            <Badge variant="destructive" className="gap-1">
                <TriangleAlert className="size-3" />
                Failing
            </Badge>
        );
    }

    return <Badge variant="default">Active</Badge>;
}

type RecentFailureAlertProps = {
    count: number;
};

export function RecentFailureAlert({ count }: RecentFailureAlertProps) {
    if (count === 0) {
        return null;
    }

    return (
        <Alert variant="destructive">
            <TriangleAlert className="size-4" />
            <AlertTitle>
                {count} failed delivery{count === 1 ? '' : 's'} in the last 24
                hours
            </AlertTitle>
            <AlertDescription>
                Check the delivery logs below and retry the failed deliveries.
            </AlertDescription>
        </Alert>
    );
}

export function EndpointCard({
    url,
    events,
    secretPreview,
    lastTriggeredAt,
    onRegenerate,
}: {
    url: string;
    events: Array<{ name: string; label: string }>;
    secretPreview: string;
    lastTriggeredAt: string | null;
    onRegenerate: () => void;
}) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between gap-4">
                <CardTitle className="text-base">Endpoint</CardTitle>
                <button
                    type="button"
                    onClick={onRegenerate}
                    className="text-primary text-xs font-medium underline-offset-4 hover:underline"
                >
                    Regenerate secret
                </button>
            </CardHeader>
            <CardContent className="grid gap-4">
                <div className="grid gap-1.5">
                    <span className="text-muted-foreground text-xs">URL</span>
                    <code className="bg-muted/50 truncate rounded-md border p-2 font-mono text-xs">
                        {url}
                    </code>
                </div>
                <div className="grid gap-1.5">
                    <span className="text-muted-foreground text-xs">
                        Events
                    </span>
                    <div className="flex flex-wrap gap-1">
                        {events.map((event) => (
                            <Badge key={event.name} variant="outline">
                                {event.label}
                            </Badge>
                        ))}
                    </div>
                </div>
                <div className="grid gap-1.5">
                    <span className="text-muted-foreground text-xs">
                        Signing secret
                    </span>
                    <code className="bg-muted/50 w-fit rounded-md border px-2 py-1 font-mono text-xs">
                        {secretPreview}
                    </code>
                </div>
                <p className="text-muted-foreground text-xs">
                    Last triggered:{' '}
                    {lastTriggeredAt
                        ? new Date(lastTriggeredAt).toLocaleString(undefined, {
                              dateStyle: 'medium',
                              timeStyle: 'short',
                          })
                        : 'never'}
                </p>
            </CardContent>
        </Card>
    );
}
