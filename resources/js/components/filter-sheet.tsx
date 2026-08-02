import { SlidersHorizontal } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';

type FilterSheetProps = {
    description?: string;
    children: ReactNode;
};

export function FilterSheet({ description = 'Filter the list by the options below.', children }: FilterSheetProps) {
    return (
        <Sheet>
            <SheetTrigger asChild>
                <Button variant="outline" className="w-full md:hidden">
                    <SlidersHorizontal />
                    Filters
                </Button>
            </SheetTrigger>
            <SheetContent side="bottom">
                <SheetHeader>
                    <SheetTitle>Filters</SheetTitle>
                    <SheetDescription>{description}</SheetDescription>
                </SheetHeader>
                <div className="grid gap-4 py-4">{children}</div>
            </SheetContent>
        </Sheet>
    );
}
