import { CodeBlock, LANGUAGE_LABELS } from '@/components/code-block';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { cn } from '@/lib/utils';

interface CodeTabsProps {
    items: { language: string; code: string }[];
    className?: string;
}

export function CodeTabs({ items, className }: CodeTabsProps) {
    if (items.length === 1) {
        return (
            <CodeBlock
                code={items[0].code}
                language={items[0].language}
                className={className}
            />
        );
    }

    return (
        <Tabs
            defaultValue={items[0].language}
            className={cn('my-4', className)}
        >
            <TabsList className="mb-2 flex w-fit flex-wrap">
                {items.map((item) => (
                    <TabsTrigger key={item.language} value={item.language}>
                        {LANGUAGE_LABELS[item.language] ?? item.language}
                    </TabsTrigger>
                ))}
            </TabsList>
            {items.map((item) => (
                <TabsContent
                    key={item.language}
                    value={item.language}
                    className="mt-0"
                >
                    <CodeBlock code={item.code} language={item.language} />
                </TabsContent>
            ))}
        </Tabs>
    );
}
