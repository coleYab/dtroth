import { Check, X } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

type ResultBadgeProps = {
    result: boolean | null;
    className?: string;
};

export function ResultBadge({ result, className }: ResultBadgeProps) {
    if (result === null) {
        return (
            <Badge variant="outline" className={className}>
                —
            </Badge>
        );
    }

    if (result) {
        return (
            <Badge variant="default" className={cn('gap-1', className)}>
                <Check className="size-3" />
                True
            </Badge>
        );
    }

    return (
        <Badge variant="destructive" className={cn('gap-1', className)}>
            <X className="size-3" />
            False
        </Badge>
    );
}
