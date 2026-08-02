import { CheckCircle2, Clock3, XCircle } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import type { ConsentStatus } from '@/types';

export function ConsentStatusBadge({ status }: { status: ConsentStatus }) {
    if (status === 'active') {
        return (
            <Badge variant="default" className="gap-1">
                <CheckCircle2 className="size-3" />
                Active
            </Badge>
        );
    }

    if (status === 'revoked') {
        return (
            <Badge variant="destructive" className="gap-1">
                <XCircle className="size-3" />
                Revoked
            </Badge>
        );
    }

    return (
        <Badge variant="secondary" className="gap-1">
            <Clock3 className="size-3" />
            Expired
        </Badge>
    );
}
