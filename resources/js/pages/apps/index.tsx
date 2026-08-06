import { Head, Link, router } from '@inertiajs/react';
import { AppWindow, Plus, Search } from 'lucide-react';
import { AppLogoImage } from '@/components/app-logo-image';
import { AppPagination } from '@/components/app-pagination';
import { CreateAppDialog } from '@/components/create-app-dialog';
import { EmptyState } from '@/components/empty-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { dashboard } from '@/routes';
import { index as appsIndex, show as showApp } from '@/routes/apps';
import type { DeveloperApp, Paginated } from '@/types';

type Props = {
    apps: Paginated<DeveloperApp>;
    canCreate: boolean;
    filters: { q: string };
};

function formatCreatedAt(date: string | null): string {
    if (!date) {
        return '—';
    }

    return new Date(date).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function EnvironmentBadges({ app }: { app: DeveloperApp }) {
    return (
        <div className="flex flex-wrap gap-1">
            {app.environments.map((environment) => (
                <Badge
                    key={environment.name}
                    variant={
                        environment.name === 'production'
                            ? 'default'
                            : 'secondary'
                    }
                >
                    {environment.name === 'production'
                        ? 'Production'
                        : 'Sandbox'}
                </Badge>
            ))}
        </div>
    );
}

function AppCard({ app }: { app: DeveloperApp }) {
    return (
        <Card className="hover:border-primary/50 group relative transition-colors hover:shadow-sm">
            <CardHeader className="flex-row items-start gap-4 space-y-0">
                <AppLogoImage app={app} />
                <div className="min-w-0 flex-1">
                    <CardTitle className="truncate text-base group-hover:underline">
                        {app.name}
                    </CardTitle>
                    <CardDescription className="mt-0.5">
                        Created {formatCreatedAt(app.created_at)}
                    </CardDescription>
                </div>
            </CardHeader>

            <CardContent className="flex flex-wrap items-center gap-2">
                <EnvironmentBadges app={app} />
                <Badge
                    variant={app.status === 'active' ? 'default' : 'secondary'}
                >
                    {app.status === 'active' ? 'Active' : 'Disabled'}
                </Badge>
            </CardContent>

            <Link
                href={showApp(app.id)}
                aria-label={`View ${app.name}`}
                className="outline-hidden focus-visible:ring-ring absolute inset-0 rounded-lg focus-visible:ring-2"
            />
        </Card>
    );
}

export default function Apps({ apps, canCreate, filters }: Props) {
    function search(event: React.ChangeEvent<HTMLInputElement>) {
        router.get(
            appsIndex(),
            { q: event.target.value },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }

    return (
        <>
            <Head title="Applications" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-2xl font-semibold">Applications</h2>
                        <p className="text-muted-foreground text-sm">
                            Manage the applications registered with the
                            platform.
                        </p>
                    </div>
                    {canCreate && (
                        <CreateAppDialog
                            trigger={
                                <Button className="w-full sm:w-auto">
                                    <Plus />
                                    Create New App
                                </Button>
                            }
                        />
                    )}
                </div>

                <div className="relative w-full md:max-w-sm">
                    <Search className="text-muted-foreground pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2" />
                    <Input
                        type="search"
                        placeholder="Search by name"
                        defaultValue={filters.q}
                        onChange={search}
                        className="pl-9"
                        aria-label="Search applications"
                    />
                </div>

                {apps.data.length === 0 ? (
                    <EmptyState
                        icon={AppWindow}
                        title={
                            filters.q
                                ? 'No applications match your search'
                                : 'No applications yet'
                        }
                        description={
                            filters.q
                                ? 'Try a different name, or clear the search.'
                                : 'Create your first application to start verifying users with Fayda.'
                        }
                        action={
                            !filters.q && canCreate ? (
                                <CreateAppDialog
                                    trigger={
                                        <Button>
                                            <Plus />
                                            Create your first app
                                        </Button>
                                    }
                                />
                            ) : undefined
                        }
                    />
                ) : (
                    <>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            {apps.data.map((app) => (
                                <AppCard key={app.id} app={app} />
                            ))}
                        </div>

                        <AppPagination links={apps.meta.links} />
                    </>
                )}
            </div>
        </>
    );
}

Apps.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Applications',
            href: appsIndex(),
        },
    ],
};
