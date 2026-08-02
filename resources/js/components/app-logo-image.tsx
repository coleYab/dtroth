import { cn } from '@/lib/utils';

export function AppLogoImage({
    app,
    className,
}: {
    app: { name: string; image: string | null };
    className?: string;
}) {
    if (app.image) {
        return (
            <img
                src={app.image}
                alt=""
                className={cn('size-12 shrink-0 rounded-lg object-cover', className)}
            />
        );
    }

    return (
        <div
            aria-hidden
            className={cn(
                'flex size-12 shrink-0 items-center justify-center rounded-lg bg-muted text-lg font-semibold text-muted-foreground',
                className,
            )}
        >
            {app.name.charAt(0).toUpperCase()}
        </div>
    );
}
