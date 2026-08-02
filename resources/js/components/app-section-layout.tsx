import { Link } from '@inertiajs/react';
import { BarChart3, FileCheck, KeyRound, LayoutDashboard, Layers, Palette, Settings, ShieldCheck, Terminal, User, Webhook } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { AppLogoImage } from '@/components/app-logo-image';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editApp, show as showApp } from '@/routes/apps';
import { usage as usageRoute } from '@/routes/apps';
import { logs as logsRoute } from '@/routes/apps';
import { index as consentsIndex } from '@/routes/apps/consents';
import { index as environmentsIndex } from '@/routes/apps/environments';
import { index as keysIndex } from '@/routes/apps/keys';
import { index as webhooksIndex } from '@/routes/apps/webhooks';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { DeveloperApp } from '@/types';

export type SectionNavItem = {
    title: string;
    href: string;
    icon?: LucideIcon;
};

export function appSectionNavItems(app: DeveloperApp): SectionNavItem[] {
    return [
        {
            title: 'Overview',
            href: showApp(app.id).url,
            icon: LayoutDashboard,
        },
        {
            title: 'Settings',
            href: editApp(app.id).url,
            icon: Settings,
        },
        {
            title: 'Verification',
            href: `/apps/${app.id}/verification-settings`,
            icon: ShieldCheck,
        },
        {
            title: 'Environments',
            href: environmentsIndex(app.id).url,
            icon: Layers,
        },
        {
            title: 'Keys',
            href: keysIndex(app.id).url,
            icon: KeyRound,
        },
        {
            title: 'Webhooks',
            href: webhooksIndex(app.id).url,
            icon: Webhook,
        },
        {
            title: 'Consents',
            href: consentsIndex(app.id).url,
            icon: FileCheck,
        },
        {
            title: 'Usage',
            href: usageRoute(app.id).url,
            icon: BarChart3,
        },
        {
            title: 'Logs',
            href: logsRoute(app.id).url,
            icon: Terminal,
        },
    ];
}

export const settingsSectionNavItems: SectionNavItem[] = [
    {
        title: 'Profile',
        href: editProfile().url,
        icon: User,
    },
    {
        title: 'Security',
        href: editSecurity().url,
        icon: ShieldCheck,
    },
    {
        title: 'Appearance',
        href: editAppearance().url,
        icon: Palette,
    },
];

export function AppNav({
    items,
    active,
    label,
    className,
}: {
    items: SectionNavItem[];
    active: string;
    label?: string;
    className?: string;
}) {
    return (
        <nav
            aria-label={label ?? 'Section navigation'}
            className={cn('w-full overflow-x-auto lg:w-56 lg:shrink-0 lg:overflow-visible', className)}
        >
            {label && (
                <p className="mb-2 hidden px-2 text-xs font-medium text-muted-foreground lg:block">
                    {label}
                </p>
            )}
            <ul className="flex gap-1 lg:flex-col">
                {items.map((item) => {
                    const isActive = item.title === active;

                    return (
                        <li key={item.title} className="shrink-0">
                            <Link
                                href={item.href}
                                aria-current={isActive ? 'page' : undefined}
                                className={cn(
                                    'flex items-center gap-2 rounded-md p-2 text-sm outline-hidden transition-colors',
                                    'hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                                    isActive
                                        ? 'bg-sidebar-accent font-medium text-sidebar-accent-foreground'
                                        : 'text-sidebar-foreground',
                                )}
                            >
                                {item.icon && <item.icon className="size-4 shrink-0" />}
                                <span className="truncate">{item.title}</span>
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}

export function AppSectionLayout({
    items,
    active,
    label,
    children,
}: {
    items: SectionNavItem[];
    active: string;
    label?: string;
    children: ReactNode;
}) {
    return (
        <div className="flex flex-col gap-6 lg:flex-row lg:items-start lg:gap-8">
            <AppNav
                items={items}
                active={active}
                label={label}
                className="lg:sticky lg:top-6 lg:pt-2"
            />
            <div className="min-w-0 flex-1 space-y-6">{children}</div>
        </div>
    );
}

export function AppSectionHeader({
    app,
    actions,
    description,
}: {
    app: DeveloperApp;
    actions?: ReactNode;
    description?: string;
}) {
    return (
        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div className="flex min-w-0 items-start gap-4">
                <AppLogoImage app={app} className="mt-0.5 size-14" />
                <div className="min-w-0">
                    <h2 className="truncate text-2xl font-semibold">{app.name}</h2>
                    {description && (
                        <p className="mt-1 text-sm text-muted-foreground">{description}</p>
                    )}
                    <div className="mt-2 flex flex-wrap items-center gap-2">
                        {app.environments.map((environment) => (
                            <Badge
                                key={environment.name}
                                variant={environment.name === 'production' ? 'default' : 'secondary'}
                            >
                                {environment.name === 'production' ? 'Production' : 'Sandbox'}
                                {environment.is_live && ' — Live'}
                            </Badge>
                        ))}
                        <Badge variant={app.status === 'active' ? 'default' : 'secondary'}>
                            {app.status === 'active' ? 'Active' : 'Disabled'}
                        </Badge>
                    </div>
                </div>
            </div>
            {actions && <div className="shrink-0">{actions}</div>}
        </div>
    );
}
