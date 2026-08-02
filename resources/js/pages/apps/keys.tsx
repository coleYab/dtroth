import { Head, router, setLayoutProps } from '@inertiajs/react';
import {
    Ban,
    Info,
    KeyRound,
    MoreHorizontal,
    Plus,
    RefreshCw,
} from 'lucide-react';
import { useState } from 'react';
import {
    AppSectionHeader,
    AppSectionLayout,
    appSectionNavItems,
} from '@/components/app-section-layout';
import { CopyButton } from '@/components/copy-button';
import { EmptyState } from '@/components/empty-state';
import { GenerateKeyDialog } from '@/components/generate-key-dialog';
import { KeyRevealDialog } from '@/components/key-reveal-dialog';
import { RevokeKeyDialog } from '@/components/revoke-key-dialog';
import { RotateKeyDialog } from '@/components/rotate-key-dialog';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as appsIndex } from '@/routes/apps';
import { shortAppName } from '@/lib/utils';
import { index as keysIndex } from '@/routes/apps/keys';
import type { ApiKey, DeveloperApp, RevealedKey } from '@/types';

type Props = {
    app: DeveloperApp;
    keys: { data: ApiKey[] };
    generateOpen: boolean;
    revealedKey: RevealedKey | null;
};

type TabValue = 'all' | 'sandbox' | 'production';

function formatDate(date: string | null): string {
    if (!date) {
        return '—';
    }

    return new Date(date).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function maskKey(publicKey: string): string {
    const prefixEnd = Math.max(
        publicKey.indexOf('_', publicKey.indexOf('_') + 1) + 1,
        8,
    );

    return `${publicKey.slice(0, prefixEnd)}••••${publicKey.slice(-4)}`;
}

function environmentBadge(environment: ApiKey['environment']) {
    if (environment === 'production') {
        return <Badge>Production</Badge>;
    }

    if (environment === 'sandbox') {
        return <Badge variant="secondary">Sandbox</Badge>;
    }

    return <Badge variant="outline">Shared</Badge>;
}

function typeBadges() {
    return (
        <div className="flex gap-1">
            <Badge variant="outline">Public</Badge>
            <Badge variant="outline">Secret</Badge>
        </div>
    );
}

function KeyActions({
    apiKey,
    onRotate,
    onRevoke,
}: {
    apiKey: ApiKey;
    onRotate: (key: ApiKey) => void;
    onRevoke: (key: ApiKey) => void;
}) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label={`Actions for ${apiKey.name}`}
                    disabled={apiKey.is_revoked}
                >
                    <MoreHorizontal />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem onSelect={() => onRotate(apiKey)}>
                    <RefreshCw />
                    Rotate
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    className="text-destructive"
                    onSelect={() => onRevoke(apiKey)}
                >
                    <Ban />
                    Revoke
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function StatusBadge({ revoked }: { revoked: boolean }) {
    return revoked ? (
        <Badge variant="destructive">Revoked</Badge>
    ) : (
        <Badge variant="secondary">Active</Badge>
    );
}

export default function Keys({ app, keys, generateOpen, revealedKey }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Applications', href: appsIndex() },
            { title: shortAppName(app.name), href: `/apps/${app.id}` },
        ],
    });

    const [tab, setTab] = useState<TabValue>('all');
    const [userOpenedGenerate, setUserOpenedGenerate] = useState(false);
    const [rotateTarget, setRotateTarget] = useState<ApiKey | null>(null);
    const [revokeTarget, setRevokeTarget] = useState<ApiKey | null>(null);
    const [revealAction, setRevealAction] = useState<'generated' | 'rotated'>(
        'generated',
    );

    const generateDialogOpen =
        (userOpenedGenerate || generateOpen) && revealedKey === null;

    const freshRevokeTarget = revokeTarget
        ? (keys.data.find((key) => key.id === revokeTarget.id) ?? null)
        : null;
    const revokeDialogKey =
        freshRevokeTarget !== null && !freshRevokeTarget.is_revoked
            ? freshRevokeTarget
            : null;

    const closeGenerate = (): void => {
        setUserOpenedGenerate(false);
        router.visit(keysIndex({ app: app.id }), { replace: true });
    };

    const closeReveal = (): void => {
        setRotateTarget(null);
        setUserOpenedGenerate(false);
        router.visit(keysIndex({ app: app.id }), { replace: true });
    };

    const visibleKeys =
        tab === 'all'
            ? keys.data
            : keys.data.filter((key) => key.environment === tab);

    return (
        <>
            <Head title={`${app.name} Keys`} />

            <div className="flex flex-col gap-6">
                <AppSectionHeader
                    app={app}
                    description="API keys are used to authenticate verification requests."
                    actions={
                        <Button
                            onClick={() => {
                                setRevealAction('generated');
                                setUserOpenedGenerate(true);
                            }}
                        >
                            <Plus />
                            Generate key
                        </Button>
                    }
                />

                <AppSectionLayout
                    items={appSectionNavItems(app)}
                    active="Keys"
                    label="Application"
                >
                    <Alert>
                        <Info className="size-4" />
                        <AlertTitle>Public vs. secret keys</AlertTitle>
                        <AlertDescription>
                            The public key identifies your application and is
                            safe to share. The secret key authenticates requests
                            — treat it like a password and never expose it in
                            client-side code.
                        </AlertDescription>
                    </Alert>

                    <Card>
                        <CardHeader className="flex-row items-center justify-between gap-2 space-y-0">
                            <CardTitle>API Keys</CardTitle>
                            <Tabs
                                value={tab}
                                onValueChange={(value) =>
                                    setTab(value as TabValue)
                                }
                                className="w-full sm:w-auto"
                            >
                                <TabsList className="grid w-full grid-cols-3 sm:w-auto">
                                    <TabsTrigger value="all">All</TabsTrigger>
                                    <TabsTrigger value="sandbox">
                                        Sandbox
                                    </TabsTrigger>
                                    <TabsTrigger value="production">
                                        Production
                                    </TabsTrigger>
                                </TabsList>
                            </Tabs>
                        </CardHeader>
                        <CardContent>
                            {visibleKeys.length === 0 ? (
                                <EmptyState
                                    icon={KeyRound}
                                    title={
                                        tab === 'all'
                                            ? 'No API keys yet'
                                            : `No ${tab === 'sandbox' ? 'sandbox' : 'production'} keys`
                                    }
                                    description="Generate a key pair to authenticate verification requests in this environment."
                                />
                            ) : (
                                <>
                                    <div className="hidden md:block">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>
                                                        Key type
                                                    </TableHead>
                                                    <TableHead>
                                                        Masked value
                                                    </TableHead>
                                                    <TableHead>
                                                        Environment
                                                    </TableHead>
                                                    <TableHead>
                                                        Created
                                                    </TableHead>
                                                    <TableHead>
                                                        Last used
                                                    </TableHead>
                                                    <TableHead>
                                                        Status
                                                    </TableHead>
                                                    <TableHead className="w-12">
                                                        <span className="sr-only">
                                                            Actions
                                                        </span>
                                                    </TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {visibleKeys.map((key) => (
                                                    <TableRow
                                                        key={key.id}
                                                        className={cn(
                                                            'align-middle',
                                                            key.is_revoked &&
                                                                'opacity-60',
                                                        )}
                                                    >
                                                        <TableCell>
                                                            {typeBadges()}
                                                        </TableCell>
                                                        <TableCell>
                                                            <div className="flex items-center gap-1">
                                                                <code className="font-mono text-xs">
                                                                    {maskKey(
                                                                        key.public_key,
                                                                    )}
                                                                </code>
                                                                <CopyButton
                                                                    value={
                                                                        key.public_key
                                                                    }
                                                                    size="sm"
                                                                />
                                                            </div>
                                                        </TableCell>
                                                        <TableCell>
                                                            {environmentBadge(
                                                                key.environment,
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="text-muted-foreground">
                                                            {formatDate(
                                                                key.created_at,
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="text-muted-foreground">
                                                            {formatDate(
                                                                key.last_used_at,
                                                            )}
                                                        </TableCell>
                                                        <TableCell>
                                                            <StatusBadge
                                                                revoked={
                                                                    key.is_revoked
                                                                }
                                                            />
                                                        </TableCell>
                                                        <TableCell>
                                                            <KeyActions
                                                                apiKey={key}
                                                                onRotate={(
                                                                    target,
                                                                ) => {
                                                                    setRevealAction(
                                                                        'rotated',
                                                                    );
                                                                    setRotateTarget(
                                                                        target,
                                                                    );
                                                                }}
                                                                onRevoke={
                                                                    setRevokeTarget
                                                                }
                                                            />
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    </div>

                                    <ul className="grid gap-3 md:hidden">
                                        {visibleKeys.map((key) => (
                                            <li
                                                key={key.id}
                                                className={cn(
                                                    'rounded-lg border p-4',
                                                    key.is_revoked &&
                                                        'opacity-60',
                                                )}
                                            >
                                                <div className="flex items-start justify-between gap-2">
                                                    <div className="min-w-0">
                                                        <p className="truncate font-medium">
                                                            {key.name}
                                                        </p>
                                                        <div className="mt-1 flex flex-wrap items-center gap-1.5">
                                                            {typeBadges()}
                                                            {environmentBadge(
                                                                key.environment,
                                                            )}
                                                            <StatusBadge
                                                                revoked={
                                                                    key.is_revoked
                                                                }
                                                            />
                                                        </div>
                                                    </div>
                                                    <KeyActions
                                                        apiKey={key}
                                                        onRotate={(target) => {
                                                            setRevealAction(
                                                                'rotated',
                                                            );
                                                            setRotateTarget(
                                                                target,
                                                            );
                                                        }}
                                                        onRevoke={
                                                            setRevokeTarget
                                                        }
                                                    />
                                                </div>
                                                <div className="mt-3 flex items-center gap-1 rounded-md border bg-muted/50 p-2 pr-1">
                                                    <code className="min-w-0 flex-1 font-mono text-xs break-all">
                                                        {maskKey(
                                                            key.public_key,
                                                        )}
                                                    </code>
                                                    <CopyButton
                                                        value={key.public_key}
                                                    />
                                                </div>
                                                <div className="mt-3 grid gap-1 text-xs text-muted-foreground">
                                                    <p>
                                                        Created{' '}
                                                        {formatDate(
                                                            key.created_at,
                                                        )}
                                                    </p>
                                                    <p>
                                                        Last used{' '}
                                                        {formatDate(
                                                            key.last_used_at,
                                                        )}
                                                    </p>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                </>
                            )}
                        </CardContent>
                    </Card>
                </AppSectionLayout>
            </div>

            <GenerateKeyDialog
                key={generateDialogOpen ? 'generate-open' : 'generate-closed'}
                app={app}
                open={generateDialogOpen}
                onOpenChange={closeGenerate}
            />
            <RotateKeyDialog
                app={app}
                apiKey={rotateTarget}
                open={rotateTarget !== null && revealedKey === null}
                onOpenChange={(open) => {
                    if (!open) {
                        setRotateTarget(null);
                    }
                }}
            />
            <RevokeKeyDialog
                app={app}
                apiKey={revokeDialogKey}
                open={revokeDialogKey !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setRevokeTarget(null);
                    }
                }}
            />
            {revealedKey ? (
                <KeyRevealDialog
                    key={revealedKey.id}
                    open
                    onOpenChange={closeReveal}
                    action={revealAction}
                    revealedKey={revealedKey}
                />
            ) : null}
        </>
    );
}
