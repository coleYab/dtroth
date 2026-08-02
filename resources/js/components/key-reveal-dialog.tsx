import { AlertTriangle } from 'lucide-react';
import { useState } from 'react';
import { CopyButton } from '@/components/copy-button';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import type { RevealedKey } from '@/types';

type KeyRevealDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    action: 'generated' | 'rotated';
    revealedKey: RevealedKey;
};

function KeyRow({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <Label className="text-xs text-muted-foreground">{label}</Label>
            <div className="mt-1 flex items-center gap-1 rounded-md border bg-muted/50 p-2 pr-1">
                <code className="min-w-0 flex-1 select-all break-all font-mono text-xs">
                    {value}
                </code>
                <CopyButton value={value} />
            </div>
        </div>
    );
}

export function KeyRevealDialog({ open, onOpenChange, action, revealedKey }: KeyRevealDialogProps) {
    const [acknowledged, setAcknowledged] = useState(false);
    const [dismissed, setDismissed] = useState(false);

    const close = (): void => {
        if (acknowledged) {
            setDismissed(true);
            onOpenChange(false);
        }
    };

    if (dismissed) {
        return null;
    }

    return (
        <Dialog open={open} onOpenChange={(next) => (next ? undefined : close())}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {action === 'generated' ? 'API key generated' : 'API key rotated'}
                    </DialogTitle>
                    <DialogDescription>
                        {action === 'generated'
                            ? `A new key pair was created for the ${revealedKey.environment} environment.`
                            : `The secret for "${revealedKey.name}" was replaced. Your previous secret no longer works.`}
                    </DialogDescription>
                </DialogHeader>

                <div className="grid gap-4">
                    <Alert variant="destructive">
                        <AlertTriangle className="size-4" />
                        <AlertTitle>Copy your secret key now</AlertTitle>
                        <AlertDescription>
                            The secret is shown only once and cannot be retrieved later. Anyone with
                            the secret can make requests on behalf of your application.
                        </AlertDescription>
                    </Alert>

                    <div className="grid gap-3">
                        <KeyRow label="Public key" value={revealedKey.public_key} />
                        <KeyRow label="Secret key" value={revealedKey.secret_key} />
                    </div>

                    <label className="flex items-start gap-2 text-sm">
                        <Checkbox
                            checked={acknowledged}
                            onCheckedChange={(checked) => setAcknowledged(checked === true)}
                            className="mt-0.5"
                        />
                        <span className="text-muted-foreground">
                            I have saved the secret key in a secure place.
                        </span>
                    </label>
                </div>

                <DialogFooter className="sm:justify-between">
                    <Button type="button" variant="outline" onClick={close} disabled={!acknowledged}>
                        Done
                    </Button>
                    <CopyButton value={revealedKey.secret_key} size="default">
                        Copy secret
                    </CopyButton>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
