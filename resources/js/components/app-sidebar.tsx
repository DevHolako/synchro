import { Link, usePage } from '@inertiajs/react';
import {
    Award,
    BookOpen,
    CalendarClock,
    CalendarDays,
    CalendarX2,
    ClipboardList,
    FileSpreadsheet,
    Building2,
    FolderGit2,
    Gavel,
    GraduationCap,
    LayoutGrid,
    RotateCcw,
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
import { Permission } from '@/lib/permissions';
import { dashboard } from '@/routes';
import { index as academicStructureIndex } from '@/routes/academic-structure';
import { index as deliberationsIndex } from '@/routes/deliberations';
import { index as examsIndex } from '@/routes/exams';
import { index as importsIndex } from '@/routes/imports';
import { index as modulesIndex } from '@/routes/modules';
import { index as myGradesIndex } from '@/routes/my-grades';
import { index as retakesIndex } from '@/routes/retakes';
import { index as roomsIndex } from '@/routes/rooms';
import { index as timetableIndex } from '@/routes/timetable';
import { index as unavailabilityReviewsIndex } from '@/routes/unavailability-reviews';
import { index as unavailabilitiesIndex } from '@/routes/unavailabilities';
import { index as usersIndex } from '@/routes/users';
import type { NavItem } from '@/types';

const USER_DIRECTORY_PERMISSIONS = [
    Permission.ViewUsers,
    Permission.ManageUsers,
];

const EXAM_PERMISSIONS = [Permission.ViewExams, Permission.ManageExams];

export function AppSidebar() {
    const { t } = useTranslation();
    const { auth, pendingUnavailabilityCount } = usePage().props;
    const canViewUsers = USER_DIRECTORY_PERMISSIONS.some((permission) =>
        auth.permissions.includes(permission),
    );
    const canImport = auth.permissions.includes(Permission.ImportReferentials);
    const canDeclareUnavailability = auth.permissions.includes(
        Permission.DeclareUnavailability,
    );
    const canReviewUnavailability = auth.permissions.includes(
        Permission.ReviewUnavailability,
    );
    const canViewSchedules = auth.permissions.includes(
        Permission.ViewSchedules,
    );
    const canViewExams = EXAM_PERMISSIONS.some((permission) =>
        auth.permissions.includes(permission),
    );
    const canDeliberate = auth.permissions.includes(Permission.LockGrades);
    const canManageExams = auth.permissions.includes(Permission.ManageExams);
    const canViewOwnGrades = auth.permissions.includes(
        Permission.ViewOwnGrades,
    );

    const mainNavItems: NavItem[] = [
        {
            title: t('nav.dashboard'),
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: t('nav.teaching_spaces'),
            href: roomsIndex(),
            icon: Building2,
        },
        {
            title: t('nav.academic_structure'),
            href: academicStructureIndex(),
            icon: GraduationCap,
        },
        {
            title: t('nav.modules_catalog'),
            href: modulesIndex(),
            icon: BookOpen,
        },
        ...(canViewSchedules
            ? [
                  {
                      title: t('nav.timetable'),
                      href: timetableIndex(),
                      icon: CalendarDays,
                  },
              ]
            : []),
        ...(canViewExams
            ? [
                  {
                      title: t('nav.exams'),
                      href: examsIndex(),
                      icon: ClipboardList,
                  },
              ]
            : []),
        ...(canManageExams
            ? [
                  {
                      title: t('nav.retakes'),
                      href: retakesIndex(),
                      icon: RotateCcw,
                  },
              ]
            : []),
        ...(canDeliberate
            ? [
                  {
                      title: t('nav.deliberations'),
                      href: deliberationsIndex(),
                      icon: Gavel,
                  },
              ]
            : []),
        ...(canViewOwnGrades
            ? [
                  {
                      title: t('nav.my_grades'),
                      href: myGradesIndex(),
                      icon: Award,
                  },
              ]
            : []),
        ...(canDeclareUnavailability
            ? [
                  {
                      title: t('nav.unavailabilities'),
                      href: unavailabilitiesIndex(),
                      icon: CalendarX2,
                  },
              ]
            : []),
        ...(canReviewUnavailability
            ? [
                  {
                      title: t('nav.unavailability_reviews'),
                      href: unavailabilityReviewsIndex(),
                      icon: CalendarClock,
                      badge: pendingUnavailabilityCount ?? undefined,
                  },
              ]
            : []),
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
