import { Head, setLayoutProps, useForm } from '@inertiajs/react';
import { Info, Save } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { AppSectionHeader, AppSectionLayout, appSectionNavItems } from '@/components/app-section-layout';
import { BrandingPreviewCard } from '@/components/branding-preview-card';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { index as appsIndex } from '@/routes/apps';
import { update as updateFlow } from '@/routes/apps/verification-flows';
import type { DeveloperApp, VerificationFlow } from '@/types';

type Props = {
    app: DeveloperApp;
    flow: VerificationFlow;
};

type FlowFormData = {
    logo: File | null;
    brand_color: string;
    success_redirect_url: string;
    failure_redirect_url: string;
    callback_url: string;
};

export default function VerificationFlows({ app, flow }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Applications', href: appsIndex() },
            { title: app.name, href: `/apps/${app.id}` },
        ],
    });

    const [logoPreview, setLogoPreview] = useState<string | null>(null);

    const { data, setData, errors, processing, patch, isDirty, wasSuccessful } =
        useForm<FlowFormData>({
            logo: null,
            brand_color: flow.brand_color,
            success_redirect_url: flow.success_redirect_url ?? '',
            failure_redirect_url: flow.failure_redirect_url ?? '',
            callback_url: flow.callback_url ?? '',
        });

    function submit(event: FormEvent) {
        event.preventDefault();

        patch(updateFlow(app.id).url);
    }

    const effectiveLogo = logoPreview ?? flow.logo_url;

    return (
        <>
            <Head title={`${app.name} · Verification flows`} />

            <div className="flex flex-col gap-6">
                <AppSectionHeader
                    app={app}
                    description="Customize the branded verification screen and redirect behaviour."
                />

                <AppSectionLayout items={appSectionNavItems(app)} active="Verification" label="Application">
                    <form onSubmit={submit} className="grid gap-6" noValidate>
                        <Card>
                            <CardHeader>
                                <CardTitle>Branding</CardTitle>
                                <CardDescription>
                                    Applied to the verification screen shown to your end users.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="grid gap-6">
                                <div className="grid gap-3 sm:grid-cols-[auto_1fr] sm:items-start">
                                    <div className="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-muted">
                                        {effectiveLogo ? (
                                            <img
                                                src={effectiveLogo}
                                                alt="Verification screen logo preview"
                                                className="h-full w-full object-contain"
                                            />
                                        ) : (
                                            <span className="text-xs text-muted-foreground">
                                                No logo
                                            </span>
                                        )}
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="logo">Logo</Label>
                                        <Input
                                            id="logo"
                                            name="logo"
                                            type="file"
                                            accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                            onChange={(event) => {
                                                const file = event.target.files?.[0] ?? null;

                                                setLogoPreview(file ? URL.createObjectURL(file) : null);
                                                setData('logo', file);
                                            }}
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            Shown in the header of the verification screen. PNG,
                                            JPEG, WebP or SVG up to 2MB.
                                        </p>
                                        <InputError message={errors.logo} />
                                    </div>
                                </div>

                                <div className="grid gap-2 sm:max-w-xs">
                                    <Label htmlFor="brand_color">Brand color</Label>
                                    <div className="flex gap-2">
                                        <Input
                                            id="brand_color"
                                            name="brand_color"
                                            type="color"
                                            value={data.brand_color}
                                            onChange={(event) =>
                                                setData('brand_color', event.target.value)
                                            }
                                            className="h-9 w-14 shrink-0 p-1"
                                        />
                                        <Input
                                            type="text"
                                            value={data.brand_color}
                                            onChange={(event) => setData('brand_color', event.target.value)}
                                            placeholder="#0f172a"
                                            aria-label="Brand color hex value"
                                        />
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        Hex value, e.g. #0f172a. Used for buttons and the header.
                                    </p>
                                    <InputError message={errors.brand_color} />
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Redirects</CardTitle>
                                <CardDescription>
                                    Where the end user is sent after a verification attempt.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="grid gap-6">
                                <div className="grid gap-2">
                                    <Label htmlFor="success_redirect_url">Success redirect URL</Label>
                                    <Input
                                        id="success_redirect_url"
                                        type="url"
                                        value={data.success_redirect_url}
                                        onChange={(event) =>
                                            setData('success_redirect_url', event.target.value)
                                        }
                                        placeholder="https://example.com/verified"
                                    />
                                    <InputError message={errors.success_redirect_url} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="failure_redirect_url">Failure redirect URL</Label>
                                    <Input
                                        id="failure_redirect_url"
                                        type="url"
                                        value={data.failure_redirect_url}
                                        onChange={(event) =>
                                            setData('failure_redirect_url', event.target.value)
                                        }
                                        placeholder="https://example.com/not-verified"
                                    />
                                    <InputError message={errors.failure_redirect_url} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="callback_url">Callback URL</Label>
                                    <Input
                                        id="callback_url"
                                        type="url"
                                        value={data.callback_url}
                                        onChange={(event) => setData('callback_url', event.target.value)}
                                        placeholder="https://example.com/webhook/verification"
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        Server-to-server result delivery. Falls back to the app’s
                                        redirect URIs when left empty.
                                    </p>
                                    <InputError message={errors.callback_url} />
                                </div>

                                <Alert>
                                    <Info className="size-4" />
                                    <AlertTitle>Flow-specific overrides</AlertTitle>
                                    <AlertDescription>
                                        These URLs override the application’s registered redirect
                                        URIs for the verification flow only.
                                    </AlertDescription>
                                </Alert>
                            </CardContent>
                        </Card>

                        <div className="lg:hidden sticky bottom-0 -mx-4 border-t bg-background p-4">
                            <Button type="submit" className="w-full" disabled={processing || !isDirty}>
                                {processing && <Spinner />}
                                Save changes
                            </Button>
                            {wasSuccessful && (
                                <p className="mt-2 text-center text-xs text-muted-foreground">
                                    Changes saved.
                                </p>
                            )}
                        </div>
                    </form>

                    <div className="grid gap-6">
                        <BrandingPreviewCard
                            app={app}
                            logoUrl={effectiveLogo}
                            brandColor={data.brand_color}
                        />

                        <div className="hidden justify-end lg:flex">
                            <Button
                                type="button"
                                onClick={() => patch(updateFlow(app.id).url)}
                                disabled={processing || !isDirty}
                                className="gap-2"
                            >
                                {processing ? <Spinner /> : <Save />}
                                Save changes
                            </Button>
                        </div>
                    </div>
                </AppSectionLayout>
            </div>
        </>
    );
}
