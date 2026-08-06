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
import type { RevealedSecret } from '@/types';

type WebhookSecretRevealDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    revealedSecret: RevealedSecret;
    isRegenerated: boolean;
};

export function WebhookSecretRevealDialog({
    open,
    onOpenChange,
    revealedSecret,
    isRegenerated,
}: WebhookSecretRevealDialogProps) {
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
        <Dialog
            open={open}
            onOpenChange={(next) => (next ? undefined : close())}
        >
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {isRegenerated
                            ? 'Signing secret regenerated'
                            : 'Webhook created'}
                    </DialogTitle>
                    <DialogDescription>
                        {isRegenerated
                            ? 'The previous signing secret no longer works. Update your endpoint now.'
                            : 'Your endpoint will receive signed POST requests for the subscribed events.'}
                    </DialogDescription>
                </DialogHeader>

                <div className="grid gap-4">
                    <Alert variant="destructive">
                        <AlertTriangle className="size-4" />
                        <AlertTitle>Copy your signing secret now</AlertTitle>
                        <AlertDescription>
                            The signing secret is shown only once and cannot be
                            retrieved later. Use it to verify the{' '}
                            <code className="font-mono">X-Troth-Signature</code>{' '}
                            header on every delivery.
                        </AlertDescription>
                    </Alert>

                    <div className="grid gap-3">
                        <div>
                            <Label className="text-muted-foreground text-xs">
                                Endpoint URL
                            </Label>
                            <div className="bg-muted/50 mt-1 flex items-center gap-1 rounded-md border p-2">
                                <code className="min-w-0 flex-1 truncate font-mono text-xs">
                                    {revealedSecret.url}
                                </code>
                            </div>
                        </div>
                        <div>
                            <Label className="text-muted-foreground text-xs">
                                Signing secret
                            </Label>
                            <div className="bg-muted/50 mt-1 flex items-center gap-1 rounded-md border p-2 pr-1">
                                <code className="min-w-0 flex-1 select-all break-all font-mono text-xs">
                                    {revealedSecret.secret}
                                </code>
                                <CopyButton value={revealedSecret.secret} />
                            </div>
                        </div>
                    </div>

                    <label className="flex items-start gap-2 text-sm">
                        <Checkbox
                            checked={acknowledged}
                            onCheckedChange={(checked) =>
                                setAcknowledged(checked === true)
                            }
                            className="mt-0.5"
                        />
                        <span className="text-muted-foreground">
                            I have stored the signing secret in a secure place.
                        </span>
                    </label>
                </div>

                <DialogFooter className="sm:justify-between">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={close}
                        disabled={!acknowledged}
                    >
                        Done
                    </Button>
                    <CopyButton value={revealedSecret.secret} size="default">
                        Copy secret
                    </CopyButton>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
