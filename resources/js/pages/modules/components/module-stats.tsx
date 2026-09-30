import { BookOpen, Clock, GraduationCap, Layers } from 'lucide-react';
import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/i18n/LanguageContext';
import type { ModuleStats } from './types';

interface ModuleStatsCardsProps {
    stats: ModuleStats;
}

export function ModuleStatsCards({ stats }: ModuleStatsCardsProps) {
    const { t } = useTranslation();

    return (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium">
                        {t('modules.stats_total_modules')}
                    </CardTitle>
                    <BookOpen className="size-4 text-neutral-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">
                        {stats.total_modules}
                    </div>
                    <p className="text-xs text-neutral-500">
                        {t('modules.stats_active_modules', {
                            active: stats.active_modules,
                        })}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium">
                        {t('modules.stats_total_hours')}
                    </CardTitle>
                    <Clock className="size-4 text-blue-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {stats.total_syllabus_hours}h
                    </div>
                    <p className="text-xs text-neutral-500">
                        {t('modules.stats_hours_desc')}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium">
                        {t('modules.stats_distribution')}
                    </CardTitle>
                    <Layers className="size-4 text-emerald-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                        {t('modules.stats_hours_breakdown', {
                            lecture: stats.total_lecture_hours,
                            tp: stats.total_tp_hours,
                        })}
                    </div>
                    <p className="mt-1 text-xs text-neutral-500">
                        {t('modules.stats_distribution_desc')}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium">
                        {t('modules.stats_teachers_assigned')}
                    </CardTitle>
                    <GraduationCap className="size-4 text-indigo-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                        {stats.assigned_modules}
                        <span className="ml-1 text-sm font-normal text-neutral-400">
                            / {stats.active_modules}
                        </span>
                    </div>
                    <p className="text-xs text-neutral-500">
                        {t('modules.stats_assigned_desc', {
                            assigned: stats.assigned_modules,
                            total: stats.active_modules,
                        })}
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
