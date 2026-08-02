import { useForm } from '@inertiajs/react';
import { Plus, X } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
import { cn } from '@/lib/utils';
import { store, update } from '@/routes/apps';
import type { DeveloperApp } from '@/types';

type AppFormData = {
    name: string;
    environment: string;
    image: File | null;
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

export function AppForm({ app }: { app?: DeveloperApp }) {
    const isEditing = Boolean(app);
    const [logoPreview, setLogoPreview] = useState<string | null>(null);

    const { data, setData, errors, processing, post, patch } = useForm<AppFormData>({
        name: app?.name ?? '',
        environment: 'sandbox',
        image: null,
        redirect_uris: app?.redirect_uris.length ? app.redirect_uris : [''],
        allowed_origins: app?.allowed_origins.length ? app.allowed_origins : [''],
    });

    function submit(event: FormEvent) {
        event.preventDefault();

        if (isEditing && app) {
            patch(update(app.id).url);
        } else {
            post(store().url);
        }
    }

    function updateListItem(
        field: 'redirect_uris' | 'allowed_origins',
        index: number,
        value: string,
    ) {
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

    return (
        <form onSubmit={submit} className="grid gap-6" noValidate>
            <Card>
                <CardHeader>
                    <CardTitle>{isEditing ? 'Application settings' : 'Application details'}</CardTitle>
                    <CardDescription>
                        {isEditing
                            ? 'Update the metadata and default configuration for this application.'
                            : 'Register a new application with the Fayda verification platform.'}
                    </CardDescription>
                </CardHeader>
                <CardContent className="grid gap-6">
                    <div className="grid gap-2 md:grid-cols-2">
                        <div className="grid gap-2 md:col-span-1">
                            <Label htmlFor="name">App name</Label>
                            <Input
                                id="name"
                                name="name"
                                type="text"
                                value={data.name}
                                onChange={(event) => setData('name', event.target.value)}
                                placeholder="e.g. Fayda Checkout"
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        {!isEditing && (
                            <div className="grid gap-2">
                                <Label htmlFor="environment">Environment</Label>
                                <Select
                                    value={data.environment}
                                    onValueChange={(value) => setData('environment', value)}
                                >
                                    <SelectTrigger id="environment" className="w-full">
                                        <SelectValue placeholder="Select an environment" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="sandbox">Sandbox</SelectItem>
                                        <SelectItem value="production">Production</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.environment} />
                            </div>
                        )}
                    </div>

                    <div className="grid gap-3 sm:grid-cols-[auto_1fr] sm:items-start">
                        <div className="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-muted">
                            {logoPreview ? (
                                <img
                                    src={logoPreview}
                                    alt="App logo preview"
                                    className="h-full w-full object-cover"
                                />
                            ) : app?.image ? (
                                <img
                                    src={app.image}
                                    alt={`${app.name} logo`}
                                    className="h-full w-full object-cover"
                                />
                            ) : (
                                <Plus className="size-6 text-muted-foreground" />
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="image">Logo</Label>
                            <Input
                                id="image"
                                name="image"
                                type="file"
                                accept="image/jpeg,image/png,image/webp,image/gif"
                                onChange={(event) => {
                                    const file = event.target.files?.[0] ?? null;

                                    setLogoPreview(file ? URL.createObjectURL(file) : null);
                                    setData('image', file);
                                }}
                            />
                            <p className="text-xs text-muted-foreground">
                                PNG, JPEG, WebP or GIF up to 2MB.
                            </p>
                            <InputError message={errors.image} />
                        </div>
                    </div>

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
                                            aria-label={`Redirect URI ${index + 1}`}
                                        />
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            onClick={() => removeListItem('redirect_uris', index)}
                                            disabled={data.redirect_uris.length === 1}
                                            aria-label={`Remove redirect URI ${index + 1}`}
                                        >
                                            <X />
                                        </Button>
                                    </div>
                                    <InputError message={getArrayError(errors, 'redirect_uris', index)} />
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
                        <InputError
                            message={errors.redirect_uris}
                            className={cn(data.redirect_uris.length > 0 && 'hidden')}
                        />
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
                                            aria-label={`Allowed origin ${index + 1}`}
                                        />
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            onClick={() => removeListItem('allowed_origins', index)}
                                            disabled={data.allowed_origins.length === 1}
                                            aria-label={`Remove allowed origin ${index + 1}`}
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
                        <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                            {processing && <Spinner />}
                            {isEditing ? 'Save changes' : 'Create Application'}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </form>
    );
}
