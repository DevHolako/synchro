import { Building2, CheckCircle2, DoorClosed, Users } from 'lucide-react';
import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/i18n/LanguageContext';
import type { Stats } from './types';

interface RoomsStatsProps {
    stats: Stats;
}

export function RoomsStats({ stats }: RoomsStatsProps) {
    const { t } = useTranslation();

    return (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium">
                        {t('rooms.stats_total_spaces')}
                    </CardTitle>
                    <DoorClosed className="size-4 text-neutral-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">
                        {stats.total_rooms}
                    </div>
                    <p className="text-xs text-neutral-500">
                        {t('rooms.stats_active_across', {
                            active: stats.active_rooms,
                            campuses: stats.total_campuses,
                        })}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium">
                        {t('rooms.stats_course_capacity')}
                    </CardTitle>
                    <Users className="size-4 text-blue-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {stats.total_course_capacity}
                    </div>
                    <p className="text-xs text-neutral-500">
                        {t('rooms.stats_course_desc')}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium">
                        {t('rooms.stats_exam_capacity')}
                    </CardTitle>
                    <CheckCircle2 className="size-4 text-emerald-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                        {stats.total_exam_capacity}
                    </div>
                    <p className="text-xs text-neutral-500">
                        {t('rooms.stats_exam_desc')}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium">
                        {t('rooms.stats_buildings_campuses')}
                    </CardTitle>
                    <Building2 className="size-4 text-neutral-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">
                        {stats.total_buildings}
                    </div>
                    <p className="text-xs text-neutral-500">
                        {t('rooms.stats_campuses_desc', {
                            campuses: stats.total_campuses,
                        })}
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
