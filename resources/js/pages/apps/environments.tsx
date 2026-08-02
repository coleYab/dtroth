import { Head, setLayoutProps, useForm } from '@inertiajs/react';
import { Plus, X } from 'lucide-react';
import { AppSectionHeader, AppSectionLayout, appSectionNavItems } from '@/components/app-section-layout';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { dashboard } from '@/routes';
import { index as appsIndex } from '@/routes/apps';
import { index as environmentsIndex, update as updateEnvironment } from '@/routes/apps/environments';
import type { AppEnvironment, DeveloperApp, EnvironmentName } from '@/types';
type Props = {
    app: DeveloperApp;
};

type EnvironmentFormData = {
    is_enabled: boolean;
    is_live: boolean;
    redirect_uris: string[];
    allowed_origins: string[];
};

function getArrayError(
    errors: Record<string, string>,
    field: 'redirect_uris' | 'allowed_origins',
    index: number,
): string | undefined {
    return errors[`${field}.${index}`];
}

function EnvironmentCard({
    environment,
    app,
}: {
    environment: AppEnvironment;
    app: DeveloperApp;
}) {
    const { data, setData, errors, processing, put } = useForm<EnvironmentFormData>({
        is_enabled: environment.is_enabled,
        is_live: environment.is_live,
        redirect_uris: environment.redirect_uris.length
            ? environment.redirect_uris
            : [''],
        allowed_origins: environment.allowed_origins.length
            ? environment.allowed_origins
            : [''],
    });

    function updateListItem(field: 'redirect_uris' | 'allowed_origins', index: number, value: string) {
        setData(
            field,
            data[field].map((item, itemIndex) => (itemIndex === index ? value : item)),
        );
    }

    function addListItem(field: 'redirect_uris' | 'allowed_origins') {
        setData(field, [...data[field], '']);
    }

    function removeListItem(field: 'redirect_uris' | 'allowed_origins', index: number) {
        setData(
            field,
            data[field].filter((_, itemIndex) => itemIndex !== index),
        );
    }

    const label = environment.name === 'production' ? 'Production' : 'Sandbox';

    return (
        <Card>
            <CardHeader className="flex-row items-start justify-between space-y-0">
                <div>
                    <CardTitle className="flex items-center gap-2">
                        {label}
                        {environment.is_live && <Badge variant="default">Live</Badge>}
                    </CardTitle>
                    <CardDescription className="mt-1">
                        {environment.is_live
                            ? `${label} — Live. Production traffic is being served from this environment.`
                            : `Isolated ${label.toLowerCase()} configuration for testing.`}
                    </CardDescription>
                </div>
                <div className="flex items-center gap-2">
                    <Label htmlFor={`enable-${environment.id}`} className="text-sm font-normal">
                        Enabled
                    </Label>
                    <Switch
                        id={`enable-${environment.id}`}
                        checked={data.is_enabled}
                        onCheckedChange={(checked) => setData('is_enabled', checked)}
                        aria-label={`Enable ${label} environment`}
                    />
                </div>
            </CardHeader>
            <CardContent className="grid gap-6">
                <div className="grid gap-2">
                    <Label>Redirect URIs</Label>
                    <div className="grid gap-2">
                        {data.redirect_uris.map((uri, index) => (
                            <div key={index} className="grid gap-1">
                                <div className="flex gap-2">
                                    <Input
                                        type="url"
                                        value={uri}
                                        onChange={(event) =>
                                            updateListItem('redirect_uris', index, event.target.value)
                                        }
                                        placeholder="https://example.com/callback"
                                        aria-label={`${label} redirect URI ${index + 1}`}
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        onClick={() => removeListItem('redirect_uris', index)}
                                        disabled={data.redirect_uris.length === 1}
                                        aria-label={`Remove ${label} redirect URI ${index + 1}`}
                                    >
                                        <X />
                                    </Button>
                                </div>
                                <InputError
                                    message={getArrayError(errors, 'redirect_uris', index)}
                                />
                            </div>
                        ))}
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="w-fit"
                        onClick={() => addListItem('redirect_uris')}
                    >
                        <Plus />
                        Add another URI
                    </Button>
                    <InputError message={errors.redirect_uris} />
                </div>

                <div className="grid gap-2">
                    <Label>Allowed origins (CORS)</Label>
                    <div className="grid gap-2">
                        {data.allowed_origins.map((origin, index) => (
                            <div key={index} className="grid gap-1">
                                <div className="flex gap-2">
                                    <Input
                                        type="url"
                                        value={origin}
                                        onChange={(event) =>
                                            updateListItem('allowed_origins', index, event.target.value)
                                        }
                                        placeholder="https://example.com"
                                        aria-label={`${label} allowed origin ${index + 1}`}
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        onClick={() => removeListItem('allowed_origins', index)}
                                        disabled={data.allowed_origins.length === 1}
                                        aria-label={`Remove ${label} allowed origin ${index + 1}`}
                                    >
                                        <X />
                                    </Button>
                                </div>
                                <InputError
                                    message={getArrayError(errors, 'allowed_origins', index)}
                                />
                            </div>
                        ))}
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="w-fit"
                        onClick={() => addListItem('allowed_origins')}
                    >
                        <Plus />
                        Add another origin
                    </Button>
                    <InputError message={errors.allowed_origins} />
                </div>

                <div className="flex justify-end">
                    <Button
                        type="button"
                        disabled={processing}
                        onClick={() =>
                            put(
                                updateEnvironment({ app: app.id, environment: environment.id })
                                    .url,
                            )
                        }
                        className="w-full sm:w-auto"
                    >
                        {processing && <Spinner />}
                        Save {label.toLowerCase()} environment
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}

function environmentOrder(
    environments: AppEnvironment[],
): (AppEnvironment | null)[] {
    const order: EnvironmentName[] = ['sandbox', 'production'];

    return order.map((name) => {
        return environments.find((environment) => environment.name === name) ?? null;
    });
}

export default function Environments({ app }: Props) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
            {
                title: 'Applications',
                href: appsIndex(),
            },
            {
                title: `${app.name} Environments`,
                href: environmentsIndex(app.id),
            },
        ],
    });

    const [sandbox, production] = environmentOrder(app.environments);

    return (
        <>
            <Head title={`${app.name} Environments`} />

            <div className="flex flex-col gap-6">
                <AppSectionHeader
                    app={app}
                    description="Configure the sandbox and production environments for this application."
                />

                <AppSectionLayout items={appSectionNavItems(app)} active="Environments" label="Application">
                    <p className="text-sm text-muted-foreground">
                        Fields are pre-filled from the application defaults; saving applies
                        environment-specific overrides.
                    </p>

                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:items-start">
                        {sandbox && <EnvironmentCard environment={sandbox} app={app} />}
                        {production && <EnvironmentCard environment={production} app={app} />}
                    </div>
                </AppSectionLayout>
            </div>
        </>
    );
}
