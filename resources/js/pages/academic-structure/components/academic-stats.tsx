import { Building2, Clock, GraduationCap, Users } from 'lucide-react';
import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/i18n/LanguageContext';
import type { AcademicStats } from './types';

interface AcademicStatsProps {
    stats: AcademicStats;
}

export function AcademicStatsCards({ stats }: AcademicStatsProps) {
    const { t } = useTranslation();

    return (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium">
                        {t('academic.stats_departments')}
                    </CardTitle>
                    <Building2 className="size-4 text-neutral-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">
                        {stats.total_departments}
                    </div>
                    <p className="text-xs text-neutral-500">
                        {t('academic.stats_departments_desc', {
                            count: stats.total_departments,
                        })}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium">
                        {t('academic.stats_programs')}
                    </CardTitle>
                    <GraduationCap className="size-4 text-blue-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {stats.total_programs}
                    </div>
                    <p className="text-xs text-neutral-500">
                        {t('academic.stats_modalities_breakdown', {
                            initiale: stats.programs_formation_initiale,
                            amenage: stats.programs_temps_amenage,
                        })}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium">
                        {t('academic.col_modality')}
                    </CardTitle>
                    <Clock className="size-4 text-amber-500" />
                </CardHeader>
                <CardContent>
                    <div className="flex items-center gap-3 text-sm font-semibold">
                        <span className="text-emerald-600 dark:text-emerald-400">
                            {stats.programs_formation_initiale}{' '}
                            {t('academic.modality_initiale')}
                        </span>
                        <span>/</span>
                        <span className="text-amber-600 dark:text-amber-400">
                            {stats.programs_temps_amenage}{' '}
                            {t('academic.modality_amenage')}
                        </span>
                    </div>
                    <p className="mt-1 text-xs text-neutral-500">
                        {t('academic.stats_modalities_breakdown', {
                            initiale: stats.programs_formation_initiale,
                            amenage: stats.programs_temps_amenage,
                        })}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium">
                        {t('academic.stats_enrolled_students')}
                    </CardTitle>
                    <Users className="size-4 text-indigo-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                        {stats.total_expected_headcount}
                    </div>
                    <p className="text-xs text-neutral-500">
                        {t('academic.stats_groups_across', {
                            count: stats.total_programs,
                        })}
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
