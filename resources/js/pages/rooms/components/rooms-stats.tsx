import { Building2, CheckCircle2, DoorClosed, Users } from 'lucide-react';
import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Stats } from './types';

interface RoomsStatsProps {
    stats: Stats;
}

export function RoomsStats({ stats }: RoomsStatsProps) {
    return (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium">Total Teaching Spaces</CardTitle>
                    <DoorClosed className="size-4 text-neutral-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">{stats.total_rooms}</div>
                    <p className="text-xs text-neutral-500">
                        {stats.active_rooms} active across {stats.total_campuses} campuses
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium">Course Capacity</CardTitle>
                    <Users className="size-4 text-blue-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {stats.total_course_capacity}
                    </div>
                    <p className="text-xs text-neutral-500">Standard lecture seating threshold</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium">Exam Capacity</CardTitle>
                    <CheckCircle2 className="size-4 text-emerald-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                        {stats.total_exam_capacity}
                    </div>
                    <p className="text-xs text-neutral-500">Distanced seating density (anti-cheating)</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium">Buildings & Campuses</CardTitle>
                    <Building2 className="size-4 text-neutral-500" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">{stats.total_buildings}</div>
                    <p className="text-xs text-neutral-500">{stats.total_campuses} active campuses registered</p>
                </CardContent>
            </Card>
        </div>
    );
}
