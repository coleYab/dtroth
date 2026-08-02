import { useForm } from '@inertiajs/react';
import { AlertTriangle, Ban } from 'lucide-react';
import type {FormEvent} from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { revoke } from '@/routes/apps/keys';
import type { ApiKey, DeveloperApp } from '@/types';

type RevokeKeyDialogProps = {
    app: DeveloperApp;
    apiKey: ApiKey | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export function RevokeKeyDialog({ app, apiKey, open, onOpenChange }: RevokeKeyDialogProps) {
    const { data, setData, patch, processing, errors } = useForm({ confirm: '' });

    if (!apiKey) {
        return null;
    }

    const lastFour = apiKey.secret_preview.slice(-4);
    const confirmed = data.confirm === apiKey.name || data.confirm === lastFour;

    const revokeKey = (event: FormEvent): void => {
        event.preventDefault();

        patch(revoke({ app: app.id, key: apiKey.id }).url);
    };

    return (
        <Dialog open={open} onOpenChange={(next) => (next ? undefined : onOpenChange(false))}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Revoke API key</DialogTitle>
                    <DialogDescription>
                        "{apiKey.name}" will stop working immediately. This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={revokeKey} className="grid gap-4">
                    <Alert variant="destructive">
                        <AlertTriangle className="size-4" />
                        <AlertTitle>Permanent lockout</AlertTitle>
                        <AlertDescription>
                            Anything relying on this key pair will lose access right away. The key
                            stays listed as revoked for auditing, and cannot be rotated or restored.
                        </AlertDescription>
                    </Alert>

                    <div className="grid gap-2">
                        <Label htmlFor="confirm">Type the key label or its last 4 characters to confirm</Label>
                        <Input
                            id="confirm"
                            value={data.confirm}
                            onChange={(event) => setData('confirm', event.target.value)}
                            placeholder={apiKey.name}
                            autoComplete="off"
                        />
                        {errors.confirm ? (
                            <p className="text-sm text-destructive">{errors.confirm}</p>
                        ) : null}
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                            disabled={processing}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            variant="destructive"
                            disabled={processing || !confirmed}
                        >
                            {processing ? <Spinner /> : <Ban />}
                            Revoke key
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
