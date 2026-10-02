import { Link } from '@inertiajs/react';
import {
    Award,
    Bell,
    Building2,
    CalendarDays,
    CalendarX2,
    CheckSquare,
    FileSpreadsheet,
    GraduationCap,
    UsersRound,
} from 'lucide-react';
import React, { memo } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/i18n/LanguageContext';
import { index as academicStructureIndex } from '@/routes/academic-structure';
import { index as deliberationsIndex } from '@/routes/deliberations';
import { index as examsIndex } from '@/routes/exams';
import { index as importsIndex } from '@/routes/imports';
import { index as myGradesIndex } from '@/routes/my-grades';
import { index as notificationsIndex } from '@/routes/notifications';
import { index as roomsIndex } from '@/routes/rooms';
import { index as timetableIndex } from '@/routes/timetable';
import { index as unavailabilitiesIndex } from '@/routes/unavailabilities';
import { index as unavailabilityReviewsIndex } from '@/routes/unavailability-reviews';
import { index as usersIndex } from '@/routes/users';
import type { DashboardPermissions } from './types';

interface DashboardQuickActionsProps {
    permissions: DashboardPermissions;
}

export const DashboardQuickActions = memo(function DashboardQuickActions({
    permissions,
}: DashboardQuickActionsProps) {
    const { t } = useTranslation();

    const actions = [
        ...(permissions.canViewSchedules
            ? [
                  {
                      title: t('dashboard.action_timetable_title'),
                      description: t('dashboard.action_timetable_desc'),
                      href: timetableIndex(),
                      icon: CalendarDays,
                      color: 'bg-blue-500/10 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400',
                  },
              ]
            : []),
        ...(permissions.canViewExams
            ? [
                  {
                      title: t('dashboard.action_exams_title'),
                      description: t('dashboard.action_exams_desc'),
                      href: examsIndex(),
                      icon: Award,
                      color: 'bg-purple-500/10 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400',
                  },
              ]
            : []),
        ...(permissions.canEnterGrades
            ? [
                  {
                      title: t('dashboard.action_grades_title'),
                      description: t('dashboard.action_grades_desc'),
                      href: deliberationsIndex(),
                      icon: CheckSquare,
                      color: 'bg-teal-500/10 text-teal-600 dark:bg-teal-500/20 dark:text-teal-400',
                  },
              ]
            : permissions.canViewOwnGrades
              ? [
                    {
                        title: t('dashboard.action_grades_title'),
                        description: t('dashboard.action_grades_desc'),
                        href: myGradesIndex(),
                        icon: GraduationCap,
                        color: 'bg-teal-500/10 text-teal-600 dark:bg-teal-500/20 dark:text-teal-400',
                    },
                ]
              : []),
        ...(permissions.canManageReferentials
            ? [
                  {
                      title: t('dashboard.action_rooms_title'),
                      description: t('dashboard.action_rooms_desc'),
                      href: roomsIndex(),
                      icon: Building2,
                      color: 'bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400',
                  },
                  {
                      title: t('dashboard.action_structure_title'),
                      description: t('dashboard.action_structure_desc'),
                      href: academicStructureIndex(),
                      icon: GraduationCap,
                      color: 'bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400',
                  },
              ]
            : []),
        ...(permissions.canReviewUnavailability
            ? [
                  {
                      title: t('dashboard.action_reviews_title'),
                      description: t('dashboard.action_reviews_desc'),
                      href: unavailabilityReviewsIndex(),
                      icon: CalendarX2,
                      color: 'bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400',
                  },
              ]
            : permissions.canDeclareUnavailability
              ? [
                    {
                        title: t('dashboard.action_unavailabilities_title'),
                        description: t('dashboard.action_unavailabilities_desc'),
                        href: unavailabilitiesIndex(),
                        icon: CalendarX2,
                        color: 'bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400',
                    },
                ]
              : []),
        ...(permissions.canViewUsers
            ? [
                  {
                      title: t('dashboard.action_users_title'),
                      description: t('dashboard.action_users_desc'),
                      href: usersIndex(),
                      icon: UsersRound,
                      color: 'bg-cyan-500/10 text-cyan-600 dark:bg-cyan-500/20 dark:text-cyan-400',
                  },
              ]
            : []),
        ...(permissions.canImportReferentials
            ? [
                  {
                      title: t('dashboard.action_imports_title'),
                      description: t('dashboard.action_imports_desc'),
                      href: importsIndex(),
                      icon: FileSpreadsheet,
                      color: 'bg-orange-500/10 text-orange-600 dark:bg-orange-500/20 dark:text-orange-400',
                  },
              ]
            : []),
        {
            title: t('dashboard.action_notifications_title'),
            description: t('dashboard.action_notifications_desc'),
            href: notificationsIndex(),
            icon: Bell,
            color: 'bg-rose-500/10 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400',
        },
    ];

    return (
        <Card className="border-border/60 bg-card/60 backdrop-blur-xs">
            <CardHeader className="pb-3">
                <CardTitle className="text-base font-semibold">
                    {t('dashboard.quick_actions_title')}
                </CardTitle>
                <CardDescription className="text-xs">
                    {t('dashboard.quick_actions_subtitle')}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                    {actions.map((action) => {
                        const Icon = action.icon;
                        return (
                            <Link
                                key={action.title}
                                href={action.href}
                                className="group flex items-start gap-3 rounded-xl border border-border/50 bg-background/50 p-3 transition-all hover:border-border hover:bg-accent/40 hover:shadow-xs"
                            >
                                <div
                                    className={`flex size-9 shrink-0 items-center justify-center rounded-lg transition-transform group-hover:scale-105 ${action.color}`}
                                >
                                    <Icon className="size-4.5" />
                                </div>
                                <div className="min-w-0 flex-1">
                                    <h4 className="truncate text-xs font-semibold text-foreground group-hover:text-primary">
                                        {action.title}
                                    </h4>
                                    <p className="mt-0.5 line-clamp-1 text-[11px] text-muted-foreground">
                                        {action.description}
                                    </p>
                                </div>
                            </Link>
                        );
                    })}
                </div>
            </CardContent>
        </Card>
    );
});
