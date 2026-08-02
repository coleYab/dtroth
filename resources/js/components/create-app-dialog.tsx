import type { ReactNode } from 'react';
import { useState } from 'react';
import { AppForm } from '@/components/app-form';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

interface CreateAppDialogProps {
    trigger: ReactNode;
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
}

export function CreateAppDialog({ trigger, open, onOpenChange }: CreateAppDialogProps) {
    const [internalOpen, setInternalOpen] = useState(false);

    const isControlled = open !== undefined;

    const handleOpenChange = (next: boolean) => {
        if (isControlled) {
            onOpenChange?.(next);
        } else {
            setInternalOpen(next);
        }
    };

    return (
        <Dialog open={isControlled ? open : internalOpen} onOpenChange={handleOpenChange}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Create New App</DialogTitle>
                    <DialogDescription>
                        Register a new application to start verifying users with Fayda.
                    </DialogDescription>
                </DialogHeader>
                <AppForm embedded onCancel={() => handleOpenChange(false)} />
            </DialogContent>
        </Dialog>
    );
}
