import { BookOpen, Clock, GraduationCap, Layers } from 'lucide-react';
import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { ModuleStats } from './types';

interface ModuleStatsCardsProps {
    stats: ModuleStats;
}

export function ModuleStatsCards({ stats }: ModuleStatsCardsProps) {
    return (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium">
                        Total Modules
                    </CardTitle>
                    <BookOpen className="size-4 text-neutral-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">
                        {stats.total_modules}
                    </div>
                    <p className="text-xs text-neutral-500">
                        {stats.active_modules} active across academic programs
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium">
                        Syllabus Hours
                    </CardTitle>
                    <Clock className="size-4 text-blue-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {stats.total_syllabus_hours}h
                    </div>
                    <p className="text-xs text-neutral-500">
                        Total curriculum contact teaching volume
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium">
                        Teaching Distribution
                    </CardTitle>
                    <Layers className="size-4 text-emerald-500" />
                </CardHeader>
                <CardContent>
                    <div className="flex items-center gap-2 text-sm font-semibold">
                        <span className="text-blue-600 dark:text-blue-400">
                            {stats.total_lecture_hours}h Lectures
                        </span>
                        <span>•</span>
                        <span className="text-emerald-600 dark:text-emerald-400">
                            {stats.total_tp_hours}h TP
                        </span>
                    </div>
                    <p className="mt-1 text-xs text-neutral-500">
                        Theory vs practical laboratory work
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium">
                        Staffing Coverage
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
                        Active modules with designated teacher
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
