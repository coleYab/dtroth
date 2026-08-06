import { useForm } from '@inertiajs/react';
import { AlertTriangle, RefreshCw } from 'lucide-react';
import type { FormEvent } from 'react';
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
import { Spinner } from '@/components/ui/spinner';
import { rotate } from '@/routes/apps/keys';
import type { ApiKey, DeveloperApp } from '@/types';

type RotateKeyDialogProps = {
    app: DeveloperApp;
    apiKey: ApiKey | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export function RotateKeyDialog({
    app,
    apiKey,
    open,
    onOpenChange,
}: RotateKeyDialogProps) {
    const { post, processing } = useForm();

    if (!apiKey) {
        return null;
    }

    const rotateKey = (event: FormEvent): void => {
        event.preventDefault();

        post(rotate({ app: app.id, key: apiKey.id }).url);
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => (next ? undefined : onOpenChange(false))}
        >
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Rotate API key</DialogTitle>
                    <DialogDescription>
                        A new secret is issued for "{apiKey.name}". The key
                        keeps the same public key.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={rotateKey} className="grid gap-4">
                    <Alert variant="destructive">
                        <AlertTriangle className="size-4" />
                        <AlertTitle>
                            Your current secret stops working immediately
                        </AlertTitle>
                        <AlertDescription>
                            After rotating, requests using the old secret will
                            be rejected. Update your servers to the new secret
                            as soon as it is shown.
                        </AlertDescription>
                    </Alert>

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
                            disabled={processing}
                        >
                            {processing ? <Spinner /> : <RefreshCw />}
                            Rotate key
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
