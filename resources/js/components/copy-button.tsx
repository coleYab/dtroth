import { Check, Copy } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type CopyButtonProps = {
    value: string;
    className?: string;
    size?: 'sm' | 'icon' | 'default';
    children?: ReactNode;
};

export function CopyButton({
    value,
    className,
    size = 'icon',
    children,
}: CopyButtonProps) {
    const [copied, setCopied] = useState(false);

    const handleCopy = async (): Promise<void> => {
        try {
            await navigator.clipboard.writeText(value);
            setCopied(true);
            window.setTimeout(() => setCopied(false), 2000);
        } catch {
            setCopied(false);
        }
    };

    return (
        <Button
            type="button"
            variant="ghost"
            size={size}
            onClick={handleCopy}
            aria-label={copied ? 'Copied' : 'Copy to clipboard'}
            className={cn(
                'text-muted-foreground',
                copied && 'text-green-600',
                className,
            )}
        >
            {copied ? (
                <Check className="size-4" />
            ) : (
                <Copy className="size-4" />
            )}
            {children ? <span>{copied ? 'Copied!' : children}</span> : null}
        </Button>
    );
}
