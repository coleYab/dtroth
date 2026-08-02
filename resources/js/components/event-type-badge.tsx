import { Badge } from '@/components/ui/badge';
import type { ActivityEventType } from '@/types';

const eventTypeConfig: Record<
    ActivityEventType,
    { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' }
> = {
    key_rotation: { label: 'Key Rotation', variant: 'secondary' },
    failed_verification: { label: 'Failed Verification', variant: 'destructive' },
    scope_change: { label: 'Scope Change', variant: 'outline' },
};

export function EventTypeBadge({ type }: { type: ActivityEventType }) {
    const config = eventTypeConfig[type];

    return <Badge variant={config.variant}>{config.label}</Badge>;
}
