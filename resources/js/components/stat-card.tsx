import type { LucideIcon } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

export function StatCard({
    title,
    value,
    icon: Icon,
    hint,
    alert = false,
    className,
}: {
    title: string;
    value: React.ReactNode;
    icon?: LucideIcon;
    hint?: string;
    alert?: boolean;
    className?: string;
}) {
    return (
        <Card className={className}>
            <CardContent className="flex items-center justify-between gap-4 p-6">
                <div className="min-w-0">
                    <p className="text-muted-foreground truncate text-sm">
                        {title}
                    </p>
                    <p className="mt-1 text-2xl font-semibold tabular-nums">
                        {value}
                    </p>
                    {hint && (
                        <p className="text-muted-foreground mt-1 text-xs">
                            {hint}
                        </p>
                    )}
                </div>
                {Icon && (
                    <div
                        className={cn(
                            'bg-muted flex size-10 shrink-0 items-center justify-center rounded-lg',
                            alert && 'bg-destructive/10 text-destructive',
                        )}
                    >
                        <Icon className="size-5" />
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
