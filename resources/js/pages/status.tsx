import { Head } from '@inertiajs/react';
import { Activity, AlertTriangle, CheckCircle2, CircleAlert, ShieldCheck } from 'lucide-react';
import { PageHeader } from '@/components/page-header';
import { RelativeTime } from '@/components/relative-time';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

interface StatusComponent {
    key: string;
    label: string;
    description: string;
    status: 'ok' | 'degraded' | 'down';
    lastCheckedAt: string | null;
    responseTimeMs: number | null;
    uptime30: number | null;
    uptime90: number | null;
}

interface IncidentUpdate {
    status: 'investigating' | 'identified' | 'monitoring' | 'resolved';
    message: string;
    createdAt: string;
}

interface Incident {
    id: number;
    title: string;
    component: string | null;
    startedAt: string;
    resolvedAt: string | null;
    updates: IncidentUpdate[];
}

interface StatusProps {
    overall: { label: string; tone: 'ok' | 'degraded' | 'down'; description: string };
    components: StatusComponent[];
    incidents: Incident[];
}

const componentStatusLabel: Record<StatusComponent['status'], string> = {
    ok: 'Operational',
    degraded: 'Degraded',
    down: 'Down',
};

const updateStatusLabel: Record<IncidentUpdate['status'], string> = {
    investigating: 'Investigating',
    identified: 'Identified',
    monitoring: 'Monitoring',
    resolved: 'Resolved',
};

export default function Status({ overall, components, incidents }: StatusProps) {
    return (
        <>
            <Head title="System Status" />
            <PageHeader title="System Status" description="Live status of the Troth platform and recent incident history." />

            <div className="space-y-6">
                <Alert
                    variant={overall.tone === 'ok' ? 'default' : 'destructive'}
                    className={cn(
                        'gap-3 py-4',
                        overall.tone === 'ok' &&
                            'border-emerald-500/40 bg-emerald-500/10 dark:bg-emerald-500/10',
                        overall.tone === 'degraded' && 'border-amber-500/40 bg-amber-500/10 dark:bg-amber-500/10',
                        overall.tone === 'down' && 'border-red-500/40 bg-red-500/10 dark:bg-red-500/10',
                    )}
                >
                    {overall.tone === 'ok' ? (
                        <CheckCircle2 className="size-5 text-emerald-500" />
                    ) : overall.tone === 'degraded' ? (
                        <CircleAlert className="size-5 text-amber-500" />
                    ) : (
                        <AlertTriangle className="size-5 text-red-500" />
                    )}
                    <div>
                        <AlertTitle className="flex items-center gap-2">
                            {overall.label}
                            <ShieldCheck className="size-4 text-muted-foreground" />
                        </AlertTitle>
                        <AlertDescription>{overall.description}</AlertDescription>
                    </div>
                </Alert>

                <Card>
                    <CardContent className="divide-y p-0">
                        {components.map((component) => (
                            <div key={component.key} className="flex items-center justify-between gap-3 p-4">
                                <div className="flex min-w-0 items-center gap-3">
                                    <span
                                        className={cn(
                                            'size-2.5 shrink-0 rounded-full',
                                            component.status === 'ok' && 'bg-emerald-500',
                                            component.status === 'degraded' && 'bg-amber-500',
                                            component.status === 'down' && 'bg-red-500',
                                        )}
                                    />
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium">{component.label}</p>
                                        <p className="truncate text-xs text-muted-foreground">{component.description}</p>
                                    </div>
                                </div>
                                <div className="flex shrink-0 items-center gap-2 sm:gap-3">
                                    {component.uptime30 !== null && (
                                        <span className="hidden text-xs text-muted-foreground sm:block">
                                            {component.uptime30}% · 30d
                                        </span>
                                    )}
                                    <Badge
                                        variant="outline"
                                        className={cn(
                                            component.status === 'ok' && 'border-emerald-500/40 text-emerald-600 dark:text-emerald-400',
                                            component.status === 'degraded' && 'border-amber-500/40 text-amber-600 dark:text-amber-400',
                                            component.status === 'down' && 'border-red-500/40 text-red-600 dark:text-red-400',
                                        )}
                                    >
                                        {componentStatusLabel[component.status]}
                                    </Badge>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-4">
                        <h2 className="flex items-center gap-2 text-sm font-semibold">
                            <Activity className="size-4 text-muted-foreground" />
                            Incident History
                        </h2>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Past incidents, newest first. Last check updates shown for each stage.
                        </p>

                        {incidents.length === 0 ? (
                            <div className="mt-4 rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
                                No recorded incidents. 🎉
                            </div>
                        ) : (
                            <Accordion type="single" collapsible className="mt-4">
                                {incidents.map((incident) => (
                                    <AccordionItem key={incident.id} value={`incident-${incident.id}`}>
                                        <AccordionTrigger className="gap-3 text-left">
                                            <span className="min-w-0 flex-1">
                                                <span className="block truncate text-sm font-medium">
                                                    {incident.title}
                                                </span>
                                                <span className="block text-xs text-muted-foreground">
                                                    <RelativeTime date={incident.startedAt} />
                                                    {incident.resolvedAt !== null && ' · Resolved'}
                                                </span>
                                            </span>
                                        </AccordionTrigger>
                                        <AccordionContent>
                                            <ol className="relative ml-3 border-l border-border pl-6">
                                                {incident.updates.map((update) => (
                                                    <li key={update.createdAt} className="relative pb-6 last:pb-0">
                                                        <span
                                                            className={cn(
                                                                'absolute top-1 -left-[30px] size-2.5 rounded-full border-2 border-background',
                                                                update.status === 'resolved' && 'bg-emerald-500',
                                                                update.status === 'monitoring' && 'bg-sky-500',
                                                                update.status === 'identified' && 'bg-amber-500',
                                                                update.status === 'investigating' && 'bg-red-500',
                                                            )}
                                                        />
                                                        <p className="text-xs font-semibold">
                                                            {updateStatusLabel[update.status]}
                                                            <span className="ml-2 font-normal text-muted-foreground">
                                                                <RelativeTime date={update.createdAt} />
                                                            </span>
                                                        </p>
                                                        <p className="mt-1 text-sm text-muted-foreground">
                                                            {update.message}
                                                        </p>
                                                    </li>
                                                ))}
                                            </ol>
                                        </AccordionContent>
                                    </AccordionItem>
                                ))}
                            </Accordion>
                        )}
                    </CardContent>
                </Card>

                <p className="text-xs text-muted-foreground">
                    Components are checked automatically every 5 minutes. Uptime reflects the last 30 days of checks.
                </p>
            </div>
        </>
    );
}
