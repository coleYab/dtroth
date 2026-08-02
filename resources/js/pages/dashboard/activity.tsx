import { Head, Link, router } from '@inertiajs/react';
import { CalendarDays, History, SlidersHorizontal } from 'lucide-react';
import { AppPagination } from '@/components/app-pagination';
import { EmptyState } from '@/components/empty-state';
import { EventTypeBadge } from '@/components/event-type-badge';
import { RelativeTime } from '@/components/relative-time';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { dashboard } from '@/routes';
import { show as showApp } from '@/routes/apps';
import { activity } from '@/routes/dashboard';
import type { ActivityEvent, Paginated } from '@/types';

type Filters = {
    type: string;
    from: string;
    to: string;
};

type Props = {
    events: Paginated<ActivityEvent>;
    filters: Filters;
};

const eventTypeOptions: { value: string; label: string }[] = [
    { value: '', label: 'All events' },
    { value: 'key_rotation', label: 'Key Rotations' },
    { value: 'failed_verification', label: 'Failed Verifications' },
    { value: 'scope_change', label: 'Scope Changes' },
];

export default function Activity({ events, filters }: Props) {
    function applyFilters(updates: Partial<Filters>) {
        router.get(activity(), { ...filters, ...updates }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    return (
        <>
            <Head title="Activity Log" />

            <div className="flex flex-col gap-6">
                <div>
                    <h2 className="text-2xl font-semibold">Activity Log</h2>
                    <p className="text-sm text-muted-foreground">
                        Key rotations, failed verifications, and scope changes across
                        your applications.
                    </p>
                </div>

                <div className="hidden items-end gap-4 md:flex">
                    <div className="grid gap-2">
                        <Label htmlFor="event-type">Event type</Label>
                        <Select
                            value={filters.type}
                            onValueChange={(value) => applyFilters({ type: value })}
                        >
                            <SelectTrigger id="event-type" className="w-52">
                                <SelectValue placeholder="All events" />
                            </SelectTrigger>
                            <SelectContent>
                                {eventTypeOptions.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="from">From</Label>
                        <Input
                            id="from"
                            type="date"
                            value={filters.from}
                            onChange={(event) => applyFilters({ from: event.target.value })}
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="to">To</Label>
                        <Input
                            id="to"
                            type="date"
                            value={filters.to}
                            onChange={(event) => applyFilters({ to: event.target.value })}
                        />
                    </div>
                </div>

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
                            <SheetDescription>
                                Filter the activity log by event type and date range.
                            </SheetDescription>
                        </SheetHeader>
                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="mobile-event-type">Event type</Label>
                                <Select
                                    value={filters.type}
                                    onValueChange={(value) => applyFilters({ type: value })}
                                >
                                    <SelectTrigger id="mobile-event-type" className="w-full">
                                        <SelectValue placeholder="All events" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {eventTypeOptions.map((option) => (
                                            <SelectItem key={option.value} value={option.value}>
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="mobile-from">From</Label>
                                <Input
                                    id="mobile-from"
                                    type="date"
                                    value={filters.from}
                                    onChange={(event) => applyFilters({ from: event.target.value })}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="mobile-to">To</Label>
                                <Input
                                    id="mobile-to"
                                    type="date"
                                    value={filters.to}
                                    onChange={(event) => applyFilters({ to: event.target.value })}
                                />
                            </div>
                        </div>
                    </SheetContent>
                </Sheet>

                {events.data.length === 0 ? (
                    <EmptyState
                        icon={History}
                        title="No events found"
                        description="Try adjusting the filters, or check back once your applications start generating activity."
                    />
                ) : (
                    <>
                        <div className="hidden rounded-lg border md:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Event Type</TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead>App</TableHead>
                                        <TableHead className="text-right">Timestamp</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {events.data.map((event) => (
                                        <TableRow key={event.id}>
                                            <TableCell>
                                                <EventTypeBadge type={event.type} />
                                            </TableCell>
                                            <TableCell className="max-w-md truncate">
                                                {event.description}
                                            </TableCell>
                                            <TableCell>
                                                {event.app ? (
                                                    <Link
                                                        href={showApp(event.app.id)}
                                                        className="text-primary hover:underline"
                                                    >
                                                        {event.app.name}
                                                    </Link>
                                                ) : (
                                                    <span className="text-muted-foreground">—</span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <RelativeTime
                                                    date={event.created_at}
                                                    className="text-muted-foreground"
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        <ul className="grid gap-3 md:hidden">
                            {events.data.map((event) => (
                                <li key={event.id}>
                                    <div className="rounded-lg border p-4">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <EventTypeBadge type={event.type} />
                                            <RelativeTime
                                                date={event.created_at}
                                                className="ml-auto text-xs text-muted-foreground"
                                            />
                                        </div>
                                        <p className="mt-2 text-sm">{event.description}</p>
                                        {event.app && (
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                <CalendarDays className="mr-1 inline size-3" />
                                                <Link
                                                    href={showApp(event.app.id)}
                                                    className="text-primary hover:underline"
                                                >
                                                    {event.app.name}
                                                </Link>
                                            </p>
                                        )}
                                    </div>
                                </li>
                            ))}
                        </ul>

                        <AppPagination links={events.meta.links} />
                    </>
                )}
            </div>
        </>
    );
}

Activity.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Activity Log',
            href: activity(),
        },
    ],
};
