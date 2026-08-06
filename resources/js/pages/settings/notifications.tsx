import { Head, router, useForm } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import {
    AppSectionLayout,
    settingsSectionNavItems,
} from '@/components/app-section-layout';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { update } from '@/routes/notifications';

type NotificationType = {
    key: string;
    title: string;
    description: string;
    enabled: boolean;
};

type Props = {
    types: NotificationType[];
};

export default function Notifications({ types }: Props) {
    const { data, setData, processing, errors } = useForm<{
        preferences: Record<string, boolean>;
    }>({
        preferences: Object.fromEntries(
            types.map((type) => [type.key, type.enabled]),
        ),
    });

    function save() {
        router.patch(update().url, data, {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="Notification settings" />

            <h1 className="sr-only">Notification settings</h1>

            <AppSectionLayout
                items={settingsSectionNavItems}
                active="Notifications"
                label="Account settings"
            >
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Email notifications"
                        description="Choose which alerts the team receives by email. All notifications are on by default."
                    />

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Bell className="size-4" />
                                Notification types
                            </CardTitle>
                            <CardDescription>
                                These apply to every member of your team.
                                Scheduled checks run daily and hourly; you'll
                                never receive the same alert twice in 24 hours.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {types.map((type) => (
                                <div
                                    key={type.key}
                                    className="flex items-start justify-between gap-4 rounded-md border p-4"
                                >
                                    <div className="min-w-0 space-y-1">
                                        <Label
                                            htmlFor={type.key}
                                            className="font-medium"
                                        >
                                            {type.title}
                                        </Label>
                                        <p className="text-muted-foreground text-sm">
                                            {type.description}
                                        </p>
                                    </div>
                                    <Switch
                                        id={type.key}
                                        checked={data.preferences[type.key]}
                                        onCheckedChange={(checked) => {
                                            setData('preferences', {
                                                ...data.preferences,
                                                [type.key]: checked,
                                            });
                                        }}
                                    />
                                </div>
                            ))}

                            {errors.preferences && (
                                <p className="text-destructive text-sm">
                                    {errors.preferences}
                                </p>
                            )}

                            <div className="flex justify-end">
                                <Button onClick={save} disabled={processing}>
                                    Save preferences
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </AppSectionLayout>
        </>
    );
}
