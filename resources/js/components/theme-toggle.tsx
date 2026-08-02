import { Moon, Sun } from 'lucide-react';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';

export function ThemeToggle() {
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const isDark = resolvedAppearance === 'dark';

    return (
        <div
            role="group"
            aria-label="Color scheme"
            className="inline-flex items-center rounded-full border border-border bg-muted/60 p-0.5"
        >
            <button
                type="button"
                onClick={() => updateAppearance('light')}
                aria-label="Use light mode"
                aria-pressed={!isDark}
                className={cn(
                    'flex size-7 items-center justify-center rounded-full transition-all duration-200',
                    !isDark
                        ? 'bg-background text-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground',
                )}
            >
                <Sun className="size-3.5" />
            </button>
            <button
                type="button"
                onClick={() => updateAppearance('dark')}
                aria-label="Use dark mode"
                aria-pressed={isDark}
                className={cn(
                    'flex size-7 items-center justify-center rounded-full transition-all duration-200',
                    isDark
                        ? 'bg-background text-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground',
                )}
            >
                <Moon className="size-3.5" />
            </button>
        </div>
    );
}
