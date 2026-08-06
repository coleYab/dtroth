import { zodResolver } from '@hookform/resolvers/zod';
import { Head, router, usePage } from '@inertiajs/react';
import {
    CheckCircle2,
    Loader2,
    Mail,
    MessageSquareText,
    Paperclip,
    Send,
    TicketCheck,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Form,
    FormControl,
    FormDescription,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { store as supportStore } from '@/routes/support';

interface CategoryOption {
    value: string;
    label: string;
}

interface AppOption {
    id: number;
    name: string;
}

interface FlashTicket {
    reference: string;
    subject: string;
    createdAt: string;
}

interface SupportProps {
    categories: CategoryOption[];
    apps: AppOption[];
}

const supportSchema = z.object({
    subject: z
        .string()
        .min(5, 'Please enter a subject of at least 5 characters.'),
    category: z.string().min(1, 'Please choose a category.'),
    app_id: z.string().optional(),
    description: z
        .string()
        .min(20, 'Please describe the issue in at least 20 characters.'),
});

type SupportFormValues = z.infer<typeof supportSchema>;

const MAX_ATTACHMENT_MB = 5;
const ALLOWED_ATTACHMENT_TYPES = [
    'image/png',
    'image/jpeg',
    'image/webp',
    'image/gif',
    'application/pdf',
];

export default function Support({ categories, apps }: SupportProps) {
    const page = usePage();
    const flashTicket =
        (page.props.flash as { ticket?: FlashTicket } | undefined)?.ticket ??
        null;

    const [attachment, setAttachment] = useState<File | null>(null);
    const [attachmentError, setAttachmentError] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const form = useForm<SupportFormValues>({
        resolver: zodResolver(supportSchema),
        defaultValues: {
            subject: '',
            category: 'technical_issue',
            app_id: undefined,
            description: '',
        },
    });

    useEffect(() => {
        const subject = new URLSearchParams(window.location.search).get(
            'subject',
        );

        if (subject !== null) {
            form.setValue('subject', subject.slice(0, 150));
        }
    }, [form]);

    useEffect(() => {
        const errors =
            (page.props.errors as Record<string, string> | undefined) ?? {};

        Object.entries(errors).forEach(([key, message]) => {
            form.setError(key as keyof SupportFormValues, {
                type: 'server',
                message,
            });
        });
    }, [page.props.errors, form]);

    const handleAttachment = (file: File | null) => {
        setAttachmentError(null);

        if (file === null) {
            setAttachment(null);

            return;
        }

        if (!ALLOWED_ATTACHMENT_TYPES.includes(file.type)) {
            setAttachmentError(
                'Attachments must be an image (PNG, JPG, WEBP, GIF) or PDF.',
            );

            return;
        }

        if (file.size > MAX_ATTACHMENT_MB * 1024 * 1024) {
            setAttachmentError(
                `Attachments must be ${MAX_ATTACHMENT_MB} MB or smaller.`,
            );

            return;
        }

        setAttachment(file);
    };

    const onSubmit = (values: SupportFormValues) => {
        setSubmitting(true);

        router.post(
            supportStore(),
            {
                ...values,
                app_id:
                    values.app_id !== undefined && values.app_id !== ''
                        ? Number(values.app_id)
                        : null,
                attachment,
            },
            {
                forceFormData: true,
                preserveScroll: true,
                onError: () => setSubmitting(false),
                onFinish: () => setSubmitting(false),
            },
        );
    };

    return (
        <>
            <Head title="Support" />
            <PageHeader
                title="Support"
                description="Get help from the Troth team. We respond within one business day."
            />

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    {flashTicket !== null && (
                        <Card className="border-emerald-500/40 bg-emerald-500/10 dark:bg-emerald-500/10">
                            <CardContent className="p-5">
                                <div className="flex items-start gap-3">
                                    <CheckCircle2 className="mt-0.5 size-5 shrink-0 text-emerald-500" />
                                    <div>
                                        <p className="text-sm font-semibold">
                                            Ticket submitted successfully
                                        </p>
                                        <p className="text-muted-foreground mt-1 text-sm">
                                            Your reference number is{' '}
                                            <span className="text-foreground font-mono font-medium">
                                                {flashTicket.reference}
                                            </span>
                                            . We will get back to you within one
                                            business day.
                                        </p>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            className="mt-3"
                                            onClick={() =>
                                                router.reload({
                                                    only: ['flash'],
                                                })
                                            }
                                        >
                                            Submit another
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <TicketCheck className="text-muted-foreground size-4" />
                                Open a ticket
                            </CardTitle>
                            <CardDescription>
                                Include as much detail as you can — a screenshot
                                of the error is often all we need.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Form {...form}>
                                <form
                                    onSubmit={form.handleSubmit(onSubmit)}
                                    className="space-y-4"
                                >
                                    <FormField
                                        control={form.control}
                                        name="subject"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel>Subject</FormLabel>
                                                <FormControl>
                                                    <Input
                                                        placeholder="e.g. Sandbox requests returning 429"
                                                        {...field}
                                                    />
                                                </FormControl>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <FormField
                                            control={form.control}
                                            name="category"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>
                                                        Category
                                                    </FormLabel>
                                                    <Select
                                                        value={field.value}
                                                        onValueChange={
                                                            field.onChange
                                                        }
                                                    >
                                                        <FormControl>
                                                            <SelectTrigger>
                                                                <SelectValue placeholder="Choose a category" />
                                                            </SelectTrigger>
                                                        </FormControl>
                                                        <SelectContent>
                                                            {categories.map(
                                                                (category) => (
                                                                    <SelectItem
                                                                        key={
                                                                            category.value
                                                                        }
                                                                        value={
                                                                            category.value
                                                                        }
                                                                    >
                                                                        {
                                                                            category.label
                                                                        }
                                                                    </SelectItem>
                                                                ),
                                                            )}
                                                        </SelectContent>
                                                    </Select>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />

                                        <FormField
                                            control={form.control}
                                            name="app_id"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>
                                                        Related app (optional)
                                                    </FormLabel>
                                                    <Select
                                                        value={
                                                            field.value ?? ''
                                                        }
                                                        onValueChange={(
                                                            value,
                                                        ) =>
                                                            field.onChange(
                                                                value === ''
                                                                    ? undefined
                                                                    : value,
                                                            )
                                                        }
                                                    >
                                                        <FormControl>
                                                            <SelectTrigger>
                                                                <SelectValue placeholder="No specific app" />
                                                            </SelectTrigger>
                                                        </FormControl>
                                                        <SelectContent>
                                                            <SelectItem value="">
                                                                No specific app
                                                            </SelectItem>
                                                            {apps.map((app) => (
                                                                <SelectItem
                                                                    key={app.id}
                                                                    value={String(
                                                                        app.id,
                                                                    )}
                                                                >
                                                                    {app.name}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                    <FormDescription>
                                                        Helps us investigate
                                                        faster.
                                                    </FormDescription>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />
                                    </div>

                                    <FormField
                                        control={form.control}
                                        name="description"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel>
                                                    Description
                                                </FormLabel>
                                                <FormControl>
                                                    <Textarea
                                                        rows={5}
                                                        placeholder="What happened? What did you expect instead?"
                                                        {...field}
                                                    />
                                                </FormControl>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />

                                    <div className="space-y-2">
                                        <Label htmlFor="attachment">
                                            Attachment (optional)
                                        </Label>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Label
                                                htmlFor="attachment"
                                                className="hover:bg-accent inline-flex h-9 cursor-pointer items-center gap-2 rounded-md border px-3 text-sm font-normal"
                                            >
                                                <Paperclip className="size-4" />
                                                {attachment !== null
                                                    ? attachment.name
                                                    : 'Attach a file'}
                                            </Label>
                                            <Input
                                                id="attachment"
                                                type="file"
                                                accept=".png,.jpg,.jpeg,.webp,.gif,.pdf"
                                                className="sr-only"
                                                onChange={(e) =>
                                                    handleAttachment(
                                                        e.target.files?.[0] ??
                                                            null,
                                                    )
                                                }
                                            />
                                            {attachment !== null && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        handleAttachment(null)
                                                    }
                                                >
                                                    Remove
                                                </Button>
                                            )}
                                        </div>
                                        {attachmentError !== null && (
                                            <p className="text-destructive text-sm">
                                                {attachmentError}
                                            </p>
                                        )}
                                        <p className="text-muted-foreground text-sm">
                                            Images or PDF up to{' '}
                                            {MAX_ATTACHMENT_MB} MB.
                                        </p>
                                    </div>

                                    <Button
                                        type="submit"
                                        disabled={submitting}
                                        className="gap-2"
                                    >
                                        {submitting ? (
                                            <Loader2 className="size-4 animate-spin" />
                                        ) : (
                                            <Send className="size-4" />
                                        )}
                                        {submitting
                                            ? 'Submitting...'
                                            : 'Submit ticket'}
                                    </Button>
                                </form>
                            </Form>
                        </CardContent>
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <MessageSquareText className="text-muted-foreground size-4" />
                                Prefer to email?
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <p className="text-muted-foreground">
                                You can reach us directly at any time:
                            </p>
                            <a
                                href="mailto:support@troth.dev"
                                className="underline-offset-3 inline-flex items-center gap-2 font-medium underline"
                            >
                                <Mail className="size-4" />
                                support@troth.dev
                            </a>
                            <p className="text-muted-foreground">
                                Include your app name and API key preview (never
                                the full secret) to speed things up.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
