import { useForm } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import type { FormEvent } from 'react';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/apps/keys';
import type { DeveloperApp } from '@/types';

type GenerateKeyDialogProps = {
    app: DeveloperApp;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export function GenerateKeyDialog({
    app,
    open,
    onOpenChange,
}: GenerateKeyDialogProps) {
    const { data, setData, post, processing, errors } = useForm({
        environment: app.environments.find((env) => env.name === 'sandbox')
            ? 'sandbox'
            : 'production',
        name: '',
    });

    const generate = (event: FormEvent): void => {
        event.preventDefault();

        post(store({ app: app.id }).url);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Generate API key</DialogTitle>
                    <DialogDescription>
                        A public/secret key pair is created for one environment.
                        You will see the secret only once.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={generate} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="environment">Environment</Label>
                        <Select
                            value={data.environment}
                            onValueChange={(value) =>
                                setData('environment', value)
                            }
                        >
                            <SelectTrigger id="environment" className="w-full">
                                <SelectValue placeholder="Select environment" />
                            </SelectTrigger>
                            <SelectContent>
                                {app.environments.map((env) => (
                                    <SelectItem key={env.id} value={env.name}>
                                        {env.name === 'sandbox'
                                            ? 'Sandbox'
                                            : 'Production'}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.environment ? (
                            <p className="text-destructive text-sm">
                                {errors.environment}
                            </p>
                        ) : null}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="name">Label (optional)</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(event) =>
                                setData('name', event.target.value)
                            }
                            placeholder="e.g. Mobile app"
                        />
                        {errors.name ? (
                            <p className="text-destructive text-sm">
                                {errors.name}
                            </p>
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
                        <Button type="submit" disabled={processing}>
                            {processing ? (
                                <>
                                    <Spinner />
                                    Generating…
                                </>
                            ) : (
                                <>
                                    <KeyRound />
                                    Generate
                                </>
                            )}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
