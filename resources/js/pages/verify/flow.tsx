import { Head, useForm } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowRight,
    CheckCircle2,
    Loader2,
    ShieldCheck,
    XCircle,
} from 'lucide-react';
import { useMemo } from 'react';
import type { CSSProperties } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { submit as flowSubmit } from '@/routes/verify/flow';

interface FlowBranding {
    logo_url: string | null;
    brand_color: string;
    consent_text: string;
    success_redirect_url: string | null;
    failure_redirect_url: string | null;
}

interface FlowProps {
    app: { name: string };
    branding: FlowBranding;
    query: { label: string | null; description: string | null };
    mode: string;
    state: 'valid' | 'expired' | 'used' | 'invalid' | 'success' | 'failure';
    error: { code: string; message: string } | null;
    outcome: {
        result?: boolean;
        query_id?: string;
        expires_at?: string;
        verified_at?: string;
        error?: { code: string } | null;
    } | null;
    token: string | null;
}

const isSandbox = (mode: string): boolean => mode === 'sandbox';

const formatNationalId = (digits: string): string => {
    const cleaned = digits.replace(/\D/g, '').slice(0, 15);

    return cleaned.replace(/(\d{3})(?=\d)/g, '$1 ').trim();
};

const unformatNationalId = (value: string): string => value.replace(/\D/g, '');

const maskReference = (value: string): string =>
    value.length <= 8 ? value : `${value.slice(0, 4)}…${value.slice(-4)}`;

export default function VerifyFlow({
    app,
    branding,
    query,
    mode,
    state,
    error,
    outcome,
    token,
}: FlowProps) {
    const sandbox = isSandbox(mode);

    const { data, setData, post, processing, errors } = useForm({
        national_id: '',
        consent: false,
    });

    const brandStyle = useMemo(
        () => ({ backgroundColor: branding.brand_color }) as CSSProperties,
        [branding.brand_color],
    );

    const onNationalIdChange = (value: string) => {
        setData(
            'national_id',
            sandbox ? value.slice(0, 40) : formatNationalId(value),
        );
    };

    const submit = () => {
        if (!token) {
            return;
        }

        setData('national_id', unformatNationalId(data.national_id));
        post(flowSubmit({ token }).url, {
            preserveScroll: true,
        });
    };

    if (state === 'expired' || state === 'used' || state === 'invalid') {
        const expiredCopy =
            state === 'invalid'
                ? 'This verification link is not valid. It may have been mistyped or revoked.'
                : state === 'used'
                  ? 'This verification link has already been used. Each link can only be used once, so please start a new verification to continue.'
                  : 'This verification link has expired. Please start a new verification to continue.';

        return (
            <div className="flex min-h-dvh flex-col items-center justify-center bg-zinc-50 px-4 py-12 dark:bg-zinc-950">
                <Head title="Verification link unavailable" />

                <div className="w-full max-w-md">
                    <Card className="border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <CardHeader className="items-center text-center">
                            {branding.logo_url ? (
                                <img
                                    src={branding.logo_url}
                                    alt={app.name}
                                    className="mb-2 h-12 w-12 rounded-xl object-contain"
                                />
                            ) : (
                                <div
                                    className="mb-2 flex h-12 w-12 items-center justify-center rounded-xl text-lg font-bold text-white"
                                    style={brandStyle}
                                >
                                    {app.name.slice(0, 1)}
                                </div>
                            )}
                            <CardTitle>Verification link unavailable</CardTitle>
                            <CardDescription>{expiredCopy}</CardDescription>
                        </CardHeader>
                        <CardFooter className="justify-center pb-6">
                            {branding.success_redirect_url && (
                                <a href={branding.success_redirect_url}>
                                    <Button variant="outline">
                                        Return to {app.name}
                                        <ArrowRight className="size-4" />
                                    </Button>
                                </a>
                            )}
                        </CardFooter>
                    </Card>
                </div>
            </div>
        );
    }

    if (state === 'success' || state === 'failure') {
        const succeeded = state === 'success';

        return (
            <div className="flex min-h-dvh flex-col items-center justify-center bg-zinc-50 px-4 py-12 dark:bg-zinc-950">
                <Head
                    title={
                        succeeded
                            ? 'Verification complete'
                            : 'Verification not confirmed'
                    }
                />

                <div className="w-full max-w-md">
                    <Card className="border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <CardHeader className="items-center text-center">
                            {succeeded ? (
                                <CheckCircle2 className="mb-2 size-12 text-emerald-500" />
                            ) : (
                                <XCircle className="mb-2 size-12 text-rose-500" />
                            )}
                            <CardTitle>
                                {succeeded
                                    ? 'Verification complete'
                                    : 'Verification not confirmed'}
                            </CardTitle>
                            <CardDescription>
                                {succeeded
                                    ? `Your age check for ${app.name} was successful. You can now return to ${app.name}.`
                                    : `Your age check for ${app.name} could not be confirmed. If you believe this is a mistake, please contact ${app.name} for help.`}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {outcome?.query_id && (
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">
                                        Reference
                                    </span>
                                    <span className="font-mono text-xs">
                                        {maskReference(outcome.query_id)}
                                    </span>
                                </div>
                            )}
                            {outcome?.expires_at && (
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">
                                        Valid until
                                    </span>
                                    <span>
                                        {new Date(
                                            outcome.expires_at,
                                        ).toLocaleString()}
                                    </span>
                                </div>
                            )}
                        </CardContent>
                        <CardFooter className="justify-center pb-6">
                            {succeeded && branding.success_redirect_url && (
                                <a href={branding.success_redirect_url}>
                                    <Button
                                        style={brandStyle}
                                        className="text-white"
                                    >
                                        Continue to {app.name}
                                        <ArrowRight className="size-4" />
                                    </Button>
                                </a>
                            )}
                            {!succeeded && branding.failure_redirect_url && (
                                <a href={branding.failure_redirect_url}>
                                    <Button variant="outline">
                                        Return to {app.name}
                                        <ArrowRight className="size-4" />
                                    </Button>
                                </a>
                            )}
                        </CardFooter>
                    </Card>
                </div>
            </div>
        );
    }

    return (
        <div className="flex min-h-dvh flex-col items-center justify-center bg-zinc-50 px-4 py-12 dark:bg-zinc-950">
            <Head title={`Confirm your age for ${app.name}`} />

            <div className="w-full max-w-md">
                {sandbox && (
                    <div className="mb-4 flex items-center gap-2 rounded-lg border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm text-amber-800 dark:border-amber-700/50 dark:bg-amber-950/40 dark:text-amber-200">
                        <AlertTriangle className="size-4 shrink-0" />
                        <span>
                            Test environment — use a sandbox test key such as{' '}
                            <code className="font-mono">fayda-test-a</code>.
                        </span>
                    </div>
                )}

                <Card className="border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <CardHeader className="items-center text-center">
                        {branding.logo_url ? (
                            <img
                                src={branding.logo_url}
                                alt={app.name}
                                className="mb-2 h-12 w-12 rounded-xl object-contain"
                            />
                        ) : (
                            <div
                                className="mb-2 flex h-12 w-12 items-center justify-center rounded-xl text-lg font-bold text-white"
                                style={brandStyle}
                            >
                                {app.name.slice(0, 1)}
                            </div>
                        )}
                        <CardTitle className="text-xl">
                            {query.label ?? 'Confirm your age'}
                            {!sandbox && (
                                <Badge
                                    variant="secondary"
                                    className="ml-2 align-middle"
                                >
                                    {query.label}
                                </Badge>
                            )}
                        </CardTitle>
                        <CardDescription>
                            {query.description ??
                                `${app.name} needs to confirm your age to continue.`}
                        </CardDescription>
                    </CardHeader>

                    <CardContent className="space-y-5">
                        {error && (
                            <Alert variant="destructive">
                                <AlertTitle>
                                    {error.code === 'identity_provider_declined'
                                        ? 'Verification declined'
                                        : 'We could not verify your details'}
                                </AlertTitle>
                                <AlertDescription>
                                    {error.message}
                                </AlertDescription>
                            </Alert>
                        )}

                        <div className="space-y-2">
                            <Label htmlFor="national_id">National ID</Label>
                            <Input
                                id="national_id"
                                name="national_id"
                                value={data.national_id}
                                onChange={(event) =>
                                    onNationalIdChange(event.target.value)
                                }
                                placeholder={
                                    sandbox
                                        ? 'e.g. fayda-test-a'
                                        : '000 000 000 000 000'
                                }
                                className={cn(
                                    'h-11 text-base',
                                    sandbox && 'font-mono',
                                )}
                                inputMode={sandbox ? 'text' : 'numeric'}
                                autoComplete="off"
                                autoFocus
                            />
                            {errors.national_id && (
                                <p className="text-sm text-rose-600 dark:text-rose-400">
                                    {errors.national_id}
                                </p>
                            )}
                        </div>

                        <div className="flex items-start gap-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3.5 dark:border-zinc-800 dark:bg-zinc-950/40">
                            <Checkbox
                                id="consent"
                                checked={data.consent}
                                onCheckedChange={(checked) =>
                                    setData('consent', checked === true)
                                }
                                aria-label="Consent to age verification"
                            />
                            <div className="space-y-1">
                                <Label
                                    htmlFor="consent"
                                    className="text-sm font-medium leading-snug"
                                >
                                    I consent to age verification
                                </Label>
                                <p className="text-muted-foreground text-xs leading-relaxed">
                                    {branding.consent_text}
                                </p>
                            </div>
                        </div>
                        {errors.consent && (
                            <p className="text-sm text-rose-600 dark:text-rose-400">
                                {errors.consent}
                            </p>
                        )}

                        <Button
                            type="submit"
                            className="h-11 w-full text-white disabled:opacity-60"
                            style={brandStyle}
                            disabled={processing || data.national_id === ''}
                            onClick={submit}
                        >
                            {processing ? (
                                <>
                                    <Loader2 className="size-4 animate-spin" />
                                    Checking your details…
                                </>
                            ) : (
                                <>
                                    Confirm my age
                                    <ShieldCheck className="size-4" />
                                </>
                            )}
                        </Button>
                    </CardContent>

                    <CardFooter className="justify-center border-t border-zinc-100 pb-6 dark:border-zinc-800">
                        <p className="text-muted-foreground text-xs">
                            Your national ID is checked once and never stored.
                            Only the outcome of this check is shared with{' '}
                            {app.name}.
                        </p>
                    </CardFooter>
                </Card>
            </div>
        </div>
    );
}
