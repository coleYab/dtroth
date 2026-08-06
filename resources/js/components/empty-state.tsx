import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

export function EmptyState({
    icon: Icon,
    title,
    description,
    action,
    className,
}: {
    icon: LucideIcon;
    title: string;
    description?: string;
    action?: React.ReactNode;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed p-10 text-center',
                className,
            )}
        >
            <div className="bg-muted flex size-12 items-center justify-center rounded-full">
                <Icon className="text-muted-foreground size-6" />
            </div>
            <h3 className="font-medium">{title}</h3>
            {description && (
                <p className="text-muted-foreground max-w-sm text-sm">
                    {description}
                </p>
            )}
            {action && <div className="mt-2">{action}</div>}
        </div>
    );
}
