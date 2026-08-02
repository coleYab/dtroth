import { Link } from '@inertiajs/react';
import {
    AppWindow,
    BarChart3,
    BookOpen,
    FlaskConical,
    FolderGit2,
    HeartPulse,
    History,
    LayoutGrid,
    LifeBuoy,
    Wallet,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as appsIndex } from '@/routes/apps';
import billing from '@/routes/billing';
import { activity } from '@/routes/dashboard';
import { index as docsIndex } from '@/routes/docs';
import { index as sandboxIndex } from '@/routes/sandbox';
import { index as statusIndex } from '@/routes/status';
import { index as supportIndex } from '@/routes/support';
import type { NavItem } from '@/types';

const navGroups: { label: string; items: NavItem[] }[] = [
    {
        label: 'Platform',
        items: [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
            {
                title: 'Applications',
                href: appsIndex(),
                icon: AppWindow,
            },
        ],
    },
    {
        label: 'Account',
        items: [
            {
                title: 'Activity Log',
                href: activity(),
                icon: History,
            },
            {
                title: 'Usage',
                href: billing.usage(),
                icon: BarChart3,
            },
            {
                title: 'Billing',
                href: billing.index(),
                icon: Wallet,
            },
        ],
    },
    {
        label: 'Resources',
        items: [
            {
                title: 'Documentation',
                href: docsIndex(),
                icon: BookOpen,
            },
            {
                title: 'Sandbox',
                href: sandboxIndex(),
                icon: FlaskConical,
            },
            {
                title: 'System Status',
                href: statusIndex(),
                icon: HeartPulse,
            },
            {
                title: 'Support',
                href: supportIndex(),
                icon: LifeBuoy,
            },
        ],
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/coleYab/dmin',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: docsIndex().url,
        icon: BookOpen,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {navGroups.map((group) => (
                    <NavMain key={group.label} items={group.items} label={group.label} />
                ))}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
