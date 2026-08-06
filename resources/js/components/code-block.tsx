import { Check, Copy } from 'lucide-react';
import { Highlight } from 'prism-react-renderer';
import type { Language, PrismTheme } from 'prism-react-renderer';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const codeTheme: PrismTheme = {
    plain: {
        color: 'var(--foreground)',
        backgroundColor: 'transparent',
    },
    styles: [
        {
            types: ['comment', 'prolog', 'doctype', 'cdata'],
            style: { color: 'var(--prism-comment)' },
        },
        {
            types: ['string', 'attr-value', 'char', 'regex', 'inserted'],
            style: { color: 'var(--prism-string)' },
        },
        {
            types: ['keyword', 'atrule', 'important', 'function'],
            style: { color: 'var(--prism-keyword)' },
        },
        {
            types: ['number', 'boolean', 'constant', 'symbol'],
            style: { color: 'var(--prism-number)' },
        },
        {
            types: ['punctuation', 'operator'],
            style: { color: 'var(--prism-punctuation)' },
        },
        {
            types: ['class-name', 'tag', 'builtin'],
            style: { color: 'var(--prism-class)' },
        },
        {
            types: ['property', 'attr-name', 'selector'],
            style: { color: 'var(--prism-property)' },
        },
    ],
};

export const LANGUAGE_LABELS: Record<string, string> = {
    bash: 'Bash',
    curl: 'Curl',
    js: 'JavaScript',
    javascript: 'JavaScript',
    json: 'JSON',
    python: 'Python',
    py: 'Python',
    ts: 'TypeScript',
    php: 'PHP',
};

interface CodeBlockProps {
    code: string;
    language?: string;
    className?: string;
    showHeader?: boolean;
}

export function CodeBlock({
    code,
    language = 'text',
    className,
    showHeader = true,
}: CodeBlockProps) {
    const [copied, setCopied] = useState(false);

    const copy = async () => {
        await navigator.clipboard.writeText(code);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const label = LANGUAGE_LABELS[language] ?? language;

    return (
        <div
            className={cn(
                'bg-muted/40 dark:bg-muted/10 overflow-hidden rounded-lg border',
                showHeader && 'border-t-0',
                className,
            )}
        >
            {showHeader && (
                <div className="border-border bg-muted/50 flex items-center justify-between border-b px-3 py-1.5">
                    <span className="text-muted-foreground text-[11px] font-medium uppercase tracking-wider">
                        {label}
                    </span>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={copy}
                        className="text-muted-foreground h-7 gap-1 px-2 text-[11px]"
                    >
                        {copied ? (
                            <Check className="size-3.5 text-emerald-500" />
                        ) : (
                            <Copy className="size-3.5" />
                        )}
                        {copied ? 'Copied' : 'Copy'}
                    </Button>
                </div>
            )}
            <div className="max-h-[480px] overflow-auto">
                <Highlight
                    theme={codeTheme}
                    code={code.trimEnd()}
                    language={
                        (language === 'curl' ? 'bash' : language) as Language
                    }
                >
                    {({
                        className: prismClassName,
                        style,
                        tokens,
                        getLineProps,
                        getTokenProps,
                    }) => (
                        <pre
                            className={cn(
                                prismClassName,
                                'min-w-full px-4 py-3 text-[13px] leading-relaxed',
                            )}
                            style={style}
                        >
                            {tokens.map((line, i) => (
                                <div key={i} {...getLineProps({ line })}>
                                    {line.map((token, key) => (
                                        <span
                                            key={key}
                                            {...getTokenProps({ token })}
                                        />
                                    ))}
                                </div>
                            ))}
                        </pre>
                    )}
                </Highlight>
            </div>
        </div>
    );
}
