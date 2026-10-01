import type { LucideIcon } from 'lucide-react';
import { GraduationCap, MailCheck, UserCog, Users } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/i18n/LanguageContext';
import type { UserStats } from './types';

interface StatCardProps {
    title: string;
    value: number;
    hint: string;
    icon: LucideIcon;
    accent: string;
}

function StatCard({ title, value, hint, icon: Icon, accent }: StatCardProps) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium">{title}</CardTitle>
                <Icon className={`size-4 ${accent}`} />
            </CardHeader>
            <CardContent>
                <div className="text-2xl font-bold">{value}</div>
                <p className="text-xs text-neutral-500">{hint}</p>
            </CardContent>
        </Card>
    );
}

export function UserStatsCards({ stats }: { stats: UserStats }) {
    const { t } = useTranslation();

    return (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard
                title={t('users.stats_total')}
                value={stats.total}
                hint={t('users.stats_total_desc', { active: stats.active })}
                icon={Users}
                accent="text-neutral-500"
            />
            <StatCard
                title={t('users.stats_invited')}
                value={stats.invited}
                hint={t('users.stats_invited_desc')}
                icon={MailCheck}
                accent="text-amber-500"
            />
            <StatCard
                title={t('users.stats_teachers')}
                value={stats.teachers}
                hint={t('users.stats_teachers_desc')}
                icon={UserCog}
                accent="text-indigo-500"
            />
            <StatCard
                title={t('users.stats_students')}
                value={stats.students}
                hint={t('users.stats_students_desc')}
                icon={GraduationCap}
                accent="text-emerald-500"
            />
        </div>
    );
}
