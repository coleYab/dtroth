import { Head, setLayoutProps, useForm } from '@inertiajs/react';
import { Link2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import {
    AppSectionHeader,
    AppSectionLayout,
    appSectionNavItems,
} from '@/components/app-section-layout';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { shortAppName } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as appsIndex } from '@/routes/apps';
import { store } from '@/routes/apps/webhooks';
import type { DeveloperApp, WebhookEvent } from '@/types';

type Props = {
    app: DeveloperApp;
    availableEvents: WebhookEvent[];
};

type WebhookFormData = {
    url: string;
    description: string;
    events: string[];
};

export default function CreateWebhook({ app, availableEvents }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Applications', href: appsIndex() },
            { title: shortAppName(app.name), href: `/apps/${app.id}` },
        ],
    });

    const [urlValid, setUrlValid] = useState(true);

    const { data, setData, errors, processing, post } =
        useForm<WebhookFormData>({
            url: '',
            description: '',
            events: [],
        });

    function toggleEvent(name: string, checked: boolean) {
        setData(
            'events',
            checked
                ? [...data.events, name]
                : data.events.filter((event) => event !== name),
        );
    }

    function submit(event: FormEvent) {
        event.preventDefault();

        setUrlValid(data.url.startsWith('https://'));

        post(store(app.id).url);
    }

    return (
        <>
            <Head title={`${app.name} · Add Webhook`} />

            <div className="flex flex-col gap-6">
                <AppSectionHeader
                    app={app}
                    description="Receive signed POST deliveries for verification events."
                />

                <AppSectionLayout
                    items={appSectionNavItems(app)}
                    active="Webhooks"
                    label="Application"
                >
                    <form onSubmit={submit} className="grid gap-6" noValidate>
                        <Card>
                            <CardHeader>
                                <CardTitle>Endpoint</CardTitle>
                                <CardDescription>
                                    Deliveries are sent to this URL with an
                                    HMAC-SHA256 signature in the
                                    X-Troth-Signature header.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="grid gap-6">
                                <div className="grid gap-2">
                                    <Label htmlFor="url">Endpoint URL</Label>
                                    <div className="relative">
                                        <Input
                                            id="url"
                                            name="url"
                                            type="url"
                                            value={data.url}
                                            onChange={(event) =>
                                                setData(
                                                    'url',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="https://example.com/hooks/verification"
                                            className="pl-8"
                                            aria-invalid={!urlValid}
                                        />
                                        <Link2 className="text-muted-foreground absolute left-2.5 top-1/2 size-4 -translate-y-1/2" />
                                    </div>
                                    {!urlValid && (
                                        <p className="text-destructive text-sm">
                                            The endpoint must use HTTPS.
                                        </p>
                                    )}
                                    <InputError message={errors.url} />
                                    <p className="text-muted-foreground text-xs">
                                        Must be a valid HTTPS URL. Localhost
                                        URLs are accepted in sandbox
                                        environments.
                                    </p>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="description">
                                        Description (optional)
                                    </Label>
                                    <Input
                                        id="description"
                                        name="description"
                                        value={data.description}
                                        onChange={(event) =>
                                            setData(
                                                'description',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="e.g. Production verification events"
                                    />
                                    <InputError message={errors.description} />
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Events</CardTitle>
                                <CardDescription>
                                    Choose at least one event to subscribe to.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="grid gap-3">
                                {availableEvents.map((event) => (
                                    <label
                                        key={event.name}
                                        className="flex cursor-pointer items-start gap-3 rounded-lg border p-3"
                                    >
                                        <Checkbox
                                            checked={data.events.includes(
                                                event.name,
                                            )}
                                            onCheckedChange={(checked) =>
                                                toggleEvent(
                                                    event.name,
                                                    checked === true,
                                                )
                                            }
                                            className="mt-0.5"
                                        />
                                        <span className="grid gap-0.5">
                                            <span className="text-sm font-medium">
                                                {event.label}
                                            </span>
                                            <code className="text-muted-foreground text-xs">
                                                {event.name}
                                            </code>
                                        </span>
                                    </label>
                                ))}
                                <InputError message={errors.events} />
                            </CardContent>
                        </Card>

                        <Alert>
                            <AlertTitle>About signing secrets</AlertTitle>
                            <AlertDescription>
                                After creating the webhook you will see its
                                signing secret exactly once. Keep it server-side
                                and verify the X-Troth-Signature header on every
                                delivery.
                            </AlertDescription>
                        </Alert>

                        <div className="bg-background sticky bottom-0 -mx-4 border-t p-4 lg:hidden">
                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                Create webhook
                            </Button>
                        </div>

                        <div className="hidden justify-end lg:flex">
                            <Button type="submit" disabled={processing}>
                                {processing ? <Spinner /> : null}
                                Create webhook
                            </Button>
                        </div>
                    </form>
                </AppSectionLayout>
            </div>
        </>
    );
}
