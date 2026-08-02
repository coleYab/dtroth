import { Head } from '@inertiajs/react';
import { AppForm } from '@/components/app-form';
import { dashboard } from '@/routes';
import { create as createApp, index as appsIndex } from '@/routes/apps';

export default function CreateApp() {
    return (
        <>
            <Head title="Create Application" />

            <div className="flex flex-col gap-6">
                <div>
                    <h2 className="text-2xl font-semibold">Create New App</h2>
                    <p className="text-sm text-muted-foreground">
                        Register a new application to start verifying users with Fayda.
                    </p>
                </div>

                <AppForm />
            </div>
        </>
    );
}

CreateApp.layout = {
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
            title: 'New App',
            href: createApp(),
        },
    ],
};
