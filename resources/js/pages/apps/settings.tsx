import { Head, setLayoutProps } from '@inertiajs/react';
import { AlertTriangle, Trash2 } from 'lucide-react';
import { AppForm } from '@/components/app-form';
import {
    AppSectionHeader,
    AppSectionLayout,
    appSectionNavItems,
} from '@/components/app-section-layout';
import { DeleteAppDialog } from '@/components/delete-app-dialog';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { shortAppName } from '@/lib/utils';
import { dashboard } from '@/routes';
import { edit as editApp, index as appsIndex } from '@/routes/apps';
import type { DeveloperApp } from '@/types';

type Props = {
    app: DeveloperApp;
};

export default function AppSettings({ app }: Props) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
            {
                title: 'Applications',
                href: appsIndex(),
            },
            {
                title: shortAppName(app.name),
                href: editApp(app.id),
            },
        ],
    });

    return (
        <>
            <Head title={`${app.name} Settings`} />

            <div className="flex flex-col gap-6">
                <AppSectionHeader
                    app={app}
                    description="Edit the metadata, redirect URIs, and CORS origins for this application."
                />

                <AppSectionLayout
                    items={appSectionNavItems(app)}
                    active="Settings"
                    label="Application"
                >
                    <AppForm app={app} />

                    <Card className="border-destructive/50 bg-destructive/5">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-destructive">
                                <AlertTriangle />
                                Danger Zone
                            </CardTitle>
                            <CardDescription>
                                Permanently delete this application and all of
                                its data. This action cannot be undone.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <DeleteAppDialog
                                app={app}
                                trigger={
                                    <Button
                                        variant="destructive"
                                        className="w-full sm:w-auto"
                                    >
                                        <Trash2 />
                                        Delete this application
                                    </Button>
                                }
                            />
                        </CardContent>
                    </Card>
                </AppSectionLayout>
            </div>
        </>
    );
}
