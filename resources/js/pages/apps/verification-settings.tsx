import { Head, setLayoutProps, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import type { FormEvent } from 'react';
import {
    AppSectionHeader,
    AppSectionLayout,
    appSectionNavItems,
} from '@/components/app-section-layout';
import { ConsentPreviewCard } from '@/components/consent-preview-card';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { shortAppName } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as appsIndex } from '@/routes/apps';
import { update as updateSettings } from '@/routes/apps/verification-settings';
import type {
    AgeQueryPreset,
    DeveloperApp,
    VerificationSettings,
} from '@/types';

type Props = {
    app: DeveloperApp;
    settings: VerificationSettings;
    presets: AgeQueryPreset[];
    permissions: string[];
};

type SettingsFormData = {
    consent_text: string;
    re_verify_months: string;
    presets: number[];
};

export default function VerificationSettings({
    app,
    settings,
    presets,
    permissions,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Applications', href: appsIndex() },
            { title: shortAppName(app.name), href: `/apps/${app.id}` },
        ],
    });

    const canManage = permissions.includes('manage_app');

    const { data, setData, errors, processing, patch, isDirty, wasSuccessful } =
        useForm<SettingsFormData>({
            consent_text: settings.consent_text,
            re_verify_months: String(settings.re_verify_months),
            presets: presets
                .filter((preset) => preset.is_enabled)
                .map((preset) => preset.id),
        });

    function submit(event: FormEvent) {
        event.preventDefault();

        patch(updateSettings(app.id).url);
    }

    function togglePreset(id: number, enabled: boolean) {
        setData(
            'presets',
            enabled
                ? [...data.presets, id]
                : data.presets.filter((presetId) => presetId !== id),
        );
    }

    const characterCount = data.consent_text.length;

    return (
        <>
            <Head title={`${app.name} · Verification`} />

            <div className="flex flex-col gap-6">
                <AppSectionHeader
                    app={app}
                    description="Configure the age-query presets and consent copy shown to end users."
                />

                <AppSectionLayout
                    items={appSectionNavItems(app)}
                    active="Verification"
                    label="Application"
                >
                    <form onSubmit={submit} className="grid gap-6" noValidate>
                        <Card>
                            <CardHeader>
                                <CardTitle>Verification settings</CardTitle>
                                <CardDescription>
                                    These settings apply to the default consent
                                    screen shown before an age verification.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="grid gap-6">
                                <div className="grid gap-2">
                                    <div className="flex items-baseline justify-between gap-4">
                                        <Label htmlFor="consent_text">
                                            Consent text
                                        </Label>
                                        <span className="text-muted-foreground text-xs tabular-nums">
                                            {characterCount}/500
                                        </span>
                                    </div>
                                    <Textarea
                                        id="consent_text"
                                        value={data.consent_text}
                                        onChange={(event) =>
                                            setData(
                                                'consent_text',
                                                event.target.value.slice(
                                                    0,
                                                    500,
                                                ),
                                            )
                                        }
                                        rows={5}
                                        placeholder="Your consent text…"
                                        disabled={!canManage}
                                    />
                                    <p className="text-muted-foreground text-xs">
                                        Shown on the consent screen before the
                                        verification starts.
                                    </p>
                                    <InputError message={errors.consent_text} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="re_verify_months">
                                        Re-verify interval
                                    </Label>
                                    <Select
                                        value={data.re_verify_months}
                                        onValueChange={(value) =>
                                            setData('re_verify_months', value)
                                        }
                                    >
                                        <SelectTrigger
                                            id="re_verify_months"
                                            className="w-full sm:w-64"
                                            disabled={!canManage}
                                        >
                                            <SelectValue placeholder="Select an interval" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="3">
                                                Every 3 months
                                            </SelectItem>
                                            <SelectItem value="6">
                                                Every 6 months
                                            </SelectItem>
                                            <SelectItem value="12">
                                                Every 12 months
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p className="text-muted-foreground text-xs">
                                        How often an existing consent must be
                                        re-confirmed before it can be reused.
                                    </p>
                                    <InputError
                                        message={errors.re_verify_months}
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Age-query presets</CardTitle>
                                <CardDescription>
                                    Pick which age checks are available to your
                                    integrations. Only the query pair (e.g.
                                    “18+”) and its boolean result are ever
                                    shared.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="grid gap-3">
                                {presets.map((preset) => {
                                    const enabled = data.presets.includes(
                                        preset.id,
                                    );

                                    return (
                                        <div
                                            key={preset.id}
                                            className="flex items-start justify-between gap-4 rounded-lg border p-3"
                                        >
                                            <div className="min-w-0">
                                                <p className="text-sm font-medium">
                                                    {preset.label}
                                                </p>
                                                {preset.description && (
                                                    <p className="text-muted-foreground mt-0.5 text-xs">
                                                        {preset.description}
                                                    </p>
                                                )}
                                            </div>
                                            <Switch
                                                checked={enabled}
                                                onCheckedChange={(checked) =>
                                                    togglePreset(
                                                        preset.id,
                                                        checked,
                                                    )
                                                }
                                                disabled={!canManage}
                                                aria-label={`Enable ${preset.label}`}
                                            />
                                        </div>
                                    );
                                })}
                                <InputError message={errors.presets} />
                            </CardContent>
                        </Card>

                        {canManage && (
                            <div className="bg-background sticky bottom-0 -mx-4 border-t p-4 lg:hidden">
                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={processing || !isDirty}
                                >
                                    {processing && <Spinner />}
                                    Save changes
                                </Button>
                                {wasSuccessful && (
                                    <p className="text-muted-foreground mt-2 text-center text-xs">
                                        Changes saved.
                                    </p>
                                )}
                            </div>
                        )}
                    </form>

                    <div className="grid gap-6">
                        <ConsentPreviewCard
                            consentText={data.consent_text}
                            appName={app.name}
                        />

                        {canManage && (
                            <div className="hidden justify-end lg:flex">
                                <Button
                                    type="button"
                                    onClick={() =>
                                        patch(updateSettings(app.id).url)
                                    }
                                    disabled={processing || !isDirty}
                                    className="gap-2"
                                >
                                    {processing ? <Spinner /> : <Save />}
                                    Save changes
                                </Button>
                            </div>
                        )}
                    </div>
                </AppSectionLayout>
            </div>
        </>
    );
}
