import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    FileSpreadsheet,
    Building2,
    FolderGit2,
    GraduationCap,
    LayoutGrid,
    UsersRound,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { LanguageSwitcher } from '@/components/language-switcher';
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
import { useTranslation } from '@/i18n/LanguageContext';
import { dashboard } from '@/routes';
import { index as importsIndex } from '@/routes/imports';
import { index as usersIndex } from '@/routes/users';
import type { NavItem } from '@/types';

const USER_DIRECTORY_PERMISSIONS = ['view:users', 'manage:users'];

export function AppSidebar() {
    const { t } = useTranslation();
    const { auth } = usePage().props;
    const canViewUsers = auth.permissions.some((permission) =>
        USER_DIRECTORY_PERMISSIONS.includes(permission),
    );
    const canImport = auth.permissions.includes('import:referentials');

    const mainNavItems: NavItem[] = [
        {
            title: t('nav.dashboard'),
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: t('nav.teaching_spaces'),
            href: '/rooms',
            icon: Building2,
        },
        {
            title: t('nav.academic_structure'),
            href: '/academic-structure',
            icon: GraduationCap,
        },
        {
            title: t('nav.modules_catalog'),
            href: '/modules',
            icon: BookOpen,
        },
        ...(canImport
            ? [
                  {
                      title: t('nav.imports'),
                      href: importsIndex(),
                      icon: FileSpreadsheet,
                  },
              ]
            : []),
        ...(canViewUsers
            ? [
                  {
                      title: t('nav.users'),
                      href: usersIndex(),
                      icon: UsersRound,
                  },
              ]
            : []),
    ];

    const footerNavItems: NavItem[] = [
        {
            title: t('nav.repository'),
            href: 'https://github.com/laravel/react-starter-kit',
            icon: FolderGit2,
        },
        {
            title: t('nav.documentation'),
            href: 'https://laravel.com/docs/starter-kits#react',
            icon: BookOpen,
        },
    ];

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
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <div className="px-2 py-1">
                    <LanguageSwitcher />
                </div>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
