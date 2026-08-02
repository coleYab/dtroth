import { Head, setLayoutProps, useForm } from '@inertiajs/react';
import { Check, Download, Sparkles } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';
import billing from '@/routes/billing';
import { index as billingIndex } from '@/routes/billing';
import type { BillingPlan, Invoice, SubscriptionState } from '@/types';

type Props = {
    plans: BillingPlan[];
    current_plan: string;
    subscription: SubscriptionState;
    invoices: Invoice[];
    chapa_configured: boolean;
};

export default function Billing({ plans, current_plan, subscription, invoices, chapa_configured }: Props) {
    setLayoutProps({
        breadcrumbs: [{ title: 'Billing', href: billingIndex() }],
    });

    const defaultPlan = plans.find((p) => p.slug === current_plan)?.slug ?? plans[0]?.slug ?? '';
    const [plan, setPlan] = useState(defaultPlan);
    const { setData, post, processing } = useForm<{ plan: string }>({
        plan: defaultPlan,
    });

    function upgrade(event: FormEvent) {
        event.preventDefault();

        setData('plan', plan);

        post(billing.upgrade().url);
    }

    const statusLabel = subscription.status === 'active' ? 'Active' : 'Trial';

    return (
        <>
            <Head title="Billing" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 className="text-2xl font-semibold">Billing</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Choose a plan, manage your payment method, and download invoices.
                        </p>
                    </div>
                    <Badge variant={subscription.status === 'active' ? 'default' : 'secondary'}>
                        {statusLabel} plan
                    </Badge>
                </div>

                <div className="grid gap-6">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        {plans.map((item) => {
                            const selected = plan === item.slug;

                            return (
                                <button
                                    type="button"
                                    key={item.slug}
                                    onClick={() => setPlan(item.slug)}
                                    className={cn(
                                        'rounded-xl border p-5 text-left transition-colors',
                                        selected
                                            ? 'border-primary ring-1 ring-primary'
                                            : 'hover:border-muted-foreground/40',
                                    )}
                                >
                                    <div className="flex items-center justify-between gap-2">
                                        <p className="font-semibold">{item.name}</p>
                                        {item.is_current && <Badge>Current</Badge>}
                                    </div>
                                    <p className="mt-3 text-2xl font-semibold tabular-nums">
                                        {item.price_formatted}
                                        <span className="text-sm font-normal text-muted-foreground">
                                            /month
                                        </span>
                                    </p>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        {item.description}
                                    </p>
                                    {selected && (
                                        <p className="mt-3 flex items-center gap-1 text-xs font-medium text-primary">
                                            <Check className="size-3" />
                                            Selected
                                        </p>
                                    )}
                                </button>
                            );
                        })}
                    </div>

                    <Card className={cn(!chapa_configured && 'opacity-60')}>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Sparkles className="size-4 text-muted-foreground" />
                                Upgrade
                            </CardTitle>
                            <CardDescription>
                                {chapa_configured
                                    ? 'Pay securely with Chapa. You will be redirected to the checkout.'
                                    : 'Billing is not connected yet. Payments will be enabled shortly.'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={upgrade} className="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <Button type="submit" disabled={processing || !plan || !chapa_configured}>
                                    {processing && <Spinner />}
                                    Upgrade to {plans.find((p) => p.slug === plan)?.name ?? 'plan'}
                                </Button>
                                <Button asChild type="button" variant="outline">
                                    <a href={billing.usage().url}>View usage</a>
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <div className="grid gap-4 md:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Payment method</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-3 text-sm">
                                <div className="flex items-center justify-between gap-3">
                                    <span className="text-muted-foreground">Method</span>
                                    <span>{subscription.payment_method ?? 'No payment method'}</span>
                                </div>
                                <div className="flex items-center justify-between gap-3">
                                    <span className="text-muted-foreground">Trial ends</span>
                                    <span>
                                        {subscription.trial_ends_at
                                            ? new Date(subscription.trial_ends_at).toLocaleDateString()
                                            : '—'}
                                    </span>
                                </div>
                                {subscription.renews_at && (
                                    <div className="flex items-center justify-between gap-3">
                                        <span className="text-muted-foreground">Renews</span>
                                        <span>
                                            {new Date(subscription.renews_at).toLocaleDateString()}
                                        </span>
                                    </div>
                                )}
                                <p className="text-xs text-muted-foreground">
                                    Card details are handled by Chapa and never stored by us.
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Invoices</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {invoices.length === 0 ? (
                                    <p className="py-6 text-center text-sm text-muted-foreground">
                                        No invoices yet. Your trial is free for 14 days.
                                    </p>
                                ) : (
                                    <>
                                        <Table className="hidden md:table">
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Number</TableHead>
                                                    <TableHead>Amount</TableHead>
                                                    <TableHead>Status</TableHead>
                                                    <TableHead>Paid</TableHead>
                                                    <TableHead className="w-12" />
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {invoices.map((invoice) => (
                                                    <TableRow key={invoice.id}>
                                                        <TableCell className="font-mono text-xs">
                                                            {invoice.number}
                                                        </TableCell>
                                                        <TableCell className="tabular-nums">
                                                            {invoice.amount}
                                                        </TableCell>
                                                        <TableCell>
                                                            <Badge
                                                                variant={
                                                                    invoice.status === 'paid'
                                                                        ? 'default'
                                                                        : invoice.status === 'pending'
                                                                          ? 'secondary'
                                                                          : 'destructive'
                                                                }
                                                            >
                                                                {invoice.status}
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell>
                                                            {invoice.paid_at
                                                                ? new Date(invoice.paid_at).toLocaleDateString()
                                                                : '—'}
                                                        </TableCell>
                                                        <TableCell>
                                                            {invoice.status === 'paid' && (
                                                                <Button asChild variant="ghost" size="icon">
                                                                    <a
                                                                        href={billing.invoices.download(invoice.id).url}
                                                                        aria-label={`Download invoice ${invoice.number}`}
                                                                    >
                                                                        <Download />
                                                                    </a>
                                                                </Button>
                                                            )}
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>

                                        <div className="grid gap-3 md:hidden">
                                            {invoices.map((invoice) => (
                                                <div
                                                    key={invoice.id}
                                                    className="flex items-center justify-between gap-3 rounded-lg border p-3"
                                                >
                                                    <div className="min-w-0">
                                                        <p className="font-mono text-xs">
                                                            {invoice.number}
                                                        </p>
                                                        <p className="mt-0.5 text-sm font-medium tabular-nums">
                                                            {invoice.amount}
                                                        </p>
                                                    </div>
                                                    <div className="flex shrink-0 items-center gap-2">
                                                        <Badge
                                                            variant={
                                                                invoice.status === 'paid'
                                                                    ? 'default'
                                                                    : invoice.status === 'pending'
                                                                      ? 'secondary'
                                                                      : 'destructive'
                                                            }
                                                        >
                                                            {invoice.status}
                                                        </Badge>
                                                        {invoice.status === 'paid' && (
                                                            <Button asChild variant="outline" size="sm">
                                                                <a
                                                                    href={billing.invoices.download(invoice.id).url}
                                                                >
                                                                    <Download />
                                                                    PDF
                                                                </a>
                                                            </Button>
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}
