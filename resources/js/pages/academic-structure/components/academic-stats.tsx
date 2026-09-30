import { Building2, Clock, GraduationCap, Users } from 'lucide-react';
import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { AcademicStats } from './types';

interface AcademicStatsProps {
    stats: AcademicStats;
}

export function AcademicStatsCards({ stats }: AcademicStatsProps) {
    return (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium">
                        Departments
                    </CardTitle>
                    <Building2 className="size-4 text-neutral-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">
                        {stats.total_departments}
                    </div>
                    <p className="text-xs text-neutral-500">
                        Academic faculties & divisions
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium">
                        Academic Programs
                    </CardTitle>
                    <GraduationCap className="size-4 text-blue-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {stats.total_programs}
                    </div>
                    <p className="text-xs text-neutral-500">
                        {stats.programs_formation_initiale} Initial •{' '}
                        {stats.programs_temps_amenage} Executive
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium">
                        Program Modalities
                    </CardTitle>
                    <Clock className="size-4 text-amber-500" />
                </CardHeader>
                <CardContent>
                    <div className="flex items-center gap-3 text-sm font-semibold">
                        <span className="text-emerald-600 dark:text-emerald-400">
                            {stats.programs_formation_initiale} Initial
                        </span>
                        <span>/</span>
                        <span className="text-amber-600 dark:text-amber-400">
                            {stats.programs_temps_amenage} Temps Aménagé
                        </span>
                    </div>
                    <p className="mt-1 text-xs text-neutral-500">
                        Dual-regime scheduling supported
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium">
                        Student Headcount
                    </CardTitle>
                    <Users className="size-4 text-indigo-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                        {stats.total_expected_headcount}
                    </div>
                    <p className="text-xs text-neutral-500">
                        Across {stats.total_groups} designated student groups
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
