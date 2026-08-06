import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { destroy } from '@/routes/apps';

export function DeleteAppDialog({
    app,
    trigger,
}: {
    app: { id: number; name: string };
    trigger: React.ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const [confirmation, setConfirmation] = useState('');
    const { delete: deleteApp, processing } = useForm();

    const canDelete = confirmation === app.name;

    return (
        <AlertDialog open={open} onOpenChange={setOpen}>
            <AlertDialogTrigger asChild>{trigger}</AlertDialogTrigger>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete {app.name}?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This will permanently delete the application and all of
                        its environments, API keys, calls, verifications,
                        consents, and activity. This action cannot be undone.
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <div className="grid gap-2">
                    <Label htmlFor="confirm-delete">
                        Type <span className="font-semibold">{app.name}</span>{' '}
                        to confirm
                    </Label>
                    <Input
                        id="confirm-delete"
                        value={confirmation}
                        onChange={(event) =>
                            setConfirmation(event.target.value)
                        }
                        placeholder={app.name}
                        autoComplete="off"
                    />
                </div>

                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        disabled={!canDelete || processing}
                        onClick={() =>
                            deleteApp(destroy(app.id).url, {
                                onSuccess: () => setOpen(false),
                            })
                        }
                    >
                        {processing && <Spinner />}
                        Delete application
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
