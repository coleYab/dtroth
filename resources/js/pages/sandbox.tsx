import { Head, useForm } from '@inertiajs/react';
import {
    AlertTriangle,
    Clock3,
    FlaskConical,
    Loader2,
    Send,
    Zap,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { CodeBlock } from '@/components/code-block';
import { CodeTabs } from '@/components/code-tabs';
import { PageHeader } from '@/components/page-header';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { verify as sandboxVerify } from '@/routes/sandbox';

interface SandboxApp {
    id: number;
    name: string;
}

interface SandboxIdentity {
    key: string;
    label: string;
    age: number;
}

interface SandboxOutcome {
    statusCode: number;
    durationMs: number | null;
    payload: {
        result?: boolean;
        query_id?: string;
        verified_at?: string;
        expires_at?: string;
        error?: { code: string; message: string };
    };
}

interface RateLimitInfo {
    limit: number;
    remaining: number;
    retryAfter: number | null;
}

interface SandboxProps {
    apps: SandboxApp[];
    identities: SandboxIdentity[];
    outcome: SandboxOutcome | null;
    rateLimit: RateLimitInfo;
}

const statusTone = (statusCode: number): 'green' | 'red' | 'yellow' => {
    if (statusCode >= 200 && statusCode < 300) {
        return 'green';
    }

    if (statusCode >= 500) {
        return 'red';
    }

    return 'yellow';
};

export default function Sandbox({
    apps,
    identities,
    outcome,
    rateLimit,
}: SandboxProps) {
    const { data, setData, post, processing, errors, transform } = useForm({
        app_id: apps[0]?.id ?? 0,
        age: '18',
        type: 'equal_above',
        subject: identities[0]?.key ?? '',
    });

    const [hasSent, setHasSent] = useState(false);

    const send = () => {
        transform((prev) => ({ ...prev, app_id: Number(prev.app_id) }));
        post(sandboxVerify().url, {
            preserveScroll: true,
            onSuccess: () => setHasSent(true),
        });
    };

    const selectedIdentity = identities.find(
        (identity) => identity.key === data.subject,
    );
    const selectedApp = apps.find((app) => app.id === Number(data.app_id));

    const requestJson = useMemo(
        () =>
            JSON.stringify(
                {
                    age: Number(data.age),
                    type: data.type,
                    subject: data.subject,
                },
                null,
                2,
            ),
        [data.age, data.type, data.subject],
    );

    const snippetItems = useMemo(() => {
        const apiUrl = '/api/v1/verify/age';
        const body = `{"age":${Number(data.age)},"type":"${data.type}","subject":"${data.subject}"}`;

        return [
            {
                language: 'curl',
                code: `curl -X POST "https://api.troth.dev/v1/verify/age" \\\n  -H "Authorization: Bearer sk_sandbox_..." \\\n  -H "Content-Type: application/json" \\\n  -d '${body}'`,
            },
            {
                language: 'js',
                code: `const response = await fetch('${apiUrl}', {\n  method: 'POST',\n  headers: {\n    'Authorization': 'Bearer sk_sandbox_...',\n    'Content-Type': 'application/json',\n  },\n  body: JSON.stringify(${requestJson.replace(/\n/g, '\n  ')}),\n});`,
            },
            {
                language: 'python',
                code: `import requests\n\nresponse = requests.post(\n    '${apiUrl}',\n    headers={'Authorization': 'Bearer sk_sandbox_...'},\n    json=${requestJson.replace(/\n/g, '\n    ')},\n)`,
            },
        ];
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [requestJson]);

    const responseJson = useMemo(() => {
        if (outcome === null) {
            return null;
        }

        return JSON.stringify(outcome.payload, null, 2);
    }, [outcome]);

    const tone = outcome !== null ? statusTone(outcome.statusCode) : 'green';
    const isRateLimited = outcome?.statusCode === 429;

    return (
        <>
            <Head title="Sandbox" />
            <PageHeader
                title="Sandbox"
                description="Send real verification requests against mock test identities — no code required."
            />

            <div className="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <FlaskConical className="text-muted-foreground size-4" />
                            Request Builder
                        </CardTitle>
                        <CardDescription>
                            Build a payload and send it to the sandbox API.
                            Requests hit your sandbox environment and are
                            recorded in your app logs.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="sandbox-app">Application</Label>
                                <Select
                                    value={String(data.app_id)}
                                    onValueChange={(value) =>
                                        setData('app_id', Number(value))
                                    }
                                >
                                    <SelectTrigger id="sandbox-app">
                                        <SelectValue placeholder="Select an application" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {apps.map((app) => (
                                            <SelectItem
                                                key={app.id}
                                                value={String(app.id)}
                                            >
                                                {app.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.app_id && (
                                    <p className="text-destructive text-sm">
                                        {errors.app_id}
                                    </p>
                                )}
                                {apps.length === 0 && (
                                    <p className="text-muted-foreground text-sm">
                                        No applications with a sandbox
                                        environment yet —{' '}
                                        <a
                                            href="/apps/new"
                                            className="underline-offset-3 underline"
                                        >
                                            create one
                                        </a>
                                        .
                                    </p>
                                )}
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="sandbox-age">Age</Label>
                                    <Input
                                        id="sandbox-age"
                                        type="number"
                                        min={1}
                                        max={130}
                                        value={data.age}
                                        onChange={(e) =>
                                            setData('age', e.target.value)
                                        }
                                    />
                                    {errors.age && (
                                        <p className="text-destructive text-sm">
                                            {errors.age}
                                        </p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="sandbox-type">
                                        Query type
                                    </Label>
                                    <Select
                                        value={data.type}
                                        onValueChange={(value) =>
                                            setData('type', value)
                                        }
                                    >
                                        <SelectTrigger id="sandbox-type">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="equal_above">
                                                equal_above (18+)
                                            </SelectItem>
                                            <SelectItem value="equal_below">
                                                equal_below (16−)
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.type && (
                                        <p className="text-destructive text-sm">
                                            {errors.type}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="sandbox-subject">
                                    Test identity
                                </Label>
                                <Select
                                    value={data.subject}
                                    onValueChange={(value) =>
                                        setData('subject', value)
                                    }
                                >
                                    <SelectTrigger id="sandbox-subject">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {identities.map((identity) => (
                                            <SelectItem
                                                key={identity.key}
                                                value={identity.key}
                                            >
                                                {identity.label} — age{' '}
                                                {identity.age}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.subject && (
                                    <p className="text-destructive text-sm">
                                        {errors.subject}
                                    </p>
                                )}
                            </div>
                        </div>

                        <Button
                            type="button"
                            onClick={send}
                            disabled={processing || apps.length === 0}
                            className="w-full gap-2"
                        >
                            {processing ? (
                                <Loader2 className="size-4 animate-spin" />
                            ) : (
                                <Send className="size-4" />
                            )}
                            {processing ? 'Sending...' : 'Send Request'}
                        </Button>

                        <p className="text-muted-foreground flex items-center gap-1.5 text-xs">
                            <Zap className="size-3.5 text-amber-500" />
                            {rateLimit.remaining} of {rateLimit.limit} sandbox
                            requests remaining this minute
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Clock3 className="text-muted-foreground size-4" />
                            Response
                        </CardTitle>
                        <CardDescription>
                            {outcome === null
                                ? 'Your response will appear here after you send a request.'
                                : `Request to /v1/verify/age ${hasSent ? `for ${selectedApp?.name ?? 'app'}` : ''}`}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {outcome === null ? (
                            <div className="text-muted-foreground rounded-lg border border-dashed p-8 text-center text-sm">
                                Send a request to see the live JSON response.
                            </div>
                        ) : (
                            <>
                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge
                                        variant="outline"
                                        className={cn(
                                            'gap-1.5',
                                            tone === 'green' &&
                                                'border-emerald-500/40 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
                                            tone === 'yellow' &&
                                                'border-amber-500/40 bg-amber-500/10 text-amber-600 dark:text-amber-400',
                                            tone === 'red' &&
                                                'border-red-500/40 bg-red-500/10 text-red-600 dark:text-red-400',
                                        )}
                                    >
                                        <span className="size-1.5 rounded-full bg-current" />
                                        {outcome.statusCode}{' '}
                                        {outcome.statusCode < 300
                                            ? 'OK'
                                            : outcome.statusCode < 500
                                              ? 'Error'
                                              : 'Server Error'}
                                    </Badge>
                                    {outcome.durationMs !== null && (
                                        <Badge
                                            variant="secondary"
                                            className="gap-1"
                                        >
                                            <Clock3 className="size-3" />
                                            {outcome.durationMs} ms
                                        </Badge>
                                    )}
                                </div>

                                {isRateLimited ? (
                                    <Alert variant="destructive">
                                        <AlertTriangle className="size-4" />
                                        <AlertTitle>
                                            Rate limit reached
                                        </AlertTitle>
                                        <AlertDescription>
                                            {outcome.payload.error?.message} Try
                                            again in about{' '}
                                            {rateLimit.retryAfter ?? 60}{' '}
                                            seconds.
                                        </AlertDescription>
                                    </Alert>
                                ) : outcome.payload.error ? (
                                    <Alert variant="default">
                                        <AlertTriangle className="size-4" />
                                        <AlertTitle>
                                            {outcome.statusCode} —{' '}
                                            {outcome.payload.error.code}
                                        </AlertTitle>
                                        <AlertDescription>
                                            {outcome.payload.error.message}{' '}
                                            {outcome.statusCode === 403 && (
                                                <>
                                                    Enable this query in{' '}
                                                    <a
                                                        href={`/apps/${data.app_id}/verification-settings`}
                                                        className="underline-offset-3 underline"
                                                    >
                                                        verification settings
                                                    </a>
                                                    .
                                                </>
                                            )}
                                        </AlertDescription>
                                    </Alert>
                                ) : (
                                    <div className="grid grid-cols-3 gap-2">
                                        <div className="rounded-lg border p-3">
                                            <p className="text-muted-foreground text-xs">
                                                Result
                                            </p>
                                            <p
                                                className={cn(
                                                    'text-xl font-semibold',
                                                    outcome.payload.result
                                                        ? 'text-emerald-600 dark:text-emerald-400'
                                                        : 'text-red-600 dark:text-red-400',
                                                )}
                                            >
                                                {String(outcome.payload.result)}
                                            </p>
                                        </div>
                                        <div className="col-span-2 rounded-lg border p-3">
                                            <p className="text-muted-foreground text-xs">
                                                Query ID
                                            </p>
                                            <p className="truncate font-mono text-sm">
                                                {outcome.payload.query_id}
                                            </p>
                                        </div>
                                    </div>
                                )}

                                {responseJson !== null && (
                                    <CodeBlock
                                        code={responseJson}
                                        language="json"
                                    />
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>

            {hasSent && outcome !== null && (
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            {selectedIdentity?.label} — exact request you just
                            sent
                        </CardTitle>
                        <CardDescription>
                            Copy the snippet for your stack — it reflects
                            exactly what the sandbox sent, including the test
                            identity.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <CodeTabs items={snippetItems} />
                    </CardContent>
                </Card>
            )}
        </>
    );
}
