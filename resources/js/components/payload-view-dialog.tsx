import { Braces } from 'lucide-react';
import { CopyButton } from '@/components/copy-button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type PayloadViewDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    payload: Record<string, unknown> | null;
    response?: { status: number | null; body: string | null };
};

function JsonBlock({ value }: { value: Record<string, unknown> | null }) {
    if (value === null) {
        return (
            <p className="text-sm text-muted-foreground">No payload was recorded for this request.</p>
        );
    }

    const pretty = JSON.stringify(value, null, 2);

    return (
        <div className="relative">
            <pre className="max-h-72 overflow-auto rounded-md border bg-muted/50 p-3 font-mono text-xs leading-relaxed">
                {pretty}
            </pre>
            <div className="absolute right-2 top-2">
                <CopyButton value={pretty} />
            </div>
        </div>
    );
}

export function PayloadViewDialog({ open, onOpenChange, title, payload, response }: PayloadViewDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Braces className="size-4 text-muted-foreground" />
                        {title}
                    </DialogTitle>
                    <DialogDescription>Raw request and response payloads.</DialogDescription>
                </DialogHeader>

                <div className="grid max-h-[70vh] gap-4 overflow-y-auto pr-1">
                    <div className="grid gap-2">
                        <Label className="text-xs text-muted-foreground">Request payload</Label>
                        <JsonBlock value={payload} />
                    </div>

                    {response !== undefined && (
                        <div className="grid gap-2">
                            <Label className="text-xs text-muted-foreground">Response</Label>
                            <div className={cn('rounded-md border p-3 text-xs')}>
                                <p className="mb-2 font-medium">
                                    Status: {response.status ?? '—'}
                                </p>
                                <pre className="max-h-48 overflow-auto whitespace-pre-wrap font-mono text-xs leading-relaxed">
                                    {response.body ?? 'No response body was recorded.'}
                                </pre>
                            </div>
                        </div>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
