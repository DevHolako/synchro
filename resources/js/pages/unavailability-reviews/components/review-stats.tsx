import type { LucideIcon } from 'lucide-react';
import { CircleCheck, CircleX, Hourglass } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/i18n/LanguageContext';
import type { ReviewStats } from './types';

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

export function ReviewStatsCards({ stats }: { stats: ReviewStats }) {
    const { t } = useTranslation();

    return (
        <div className="grid gap-4 sm:grid-cols-3">
            <StatCard
                title={t('unavailability_reviews.stats_pending')}
                value={stats.pending}
                hint={t('unavailability_reviews.stats_pending_desc')}
                icon={Hourglass}
                accent="text-amber-500"
            />
            <StatCard
                title={t('unavailability_reviews.stats_approved')}
                value={stats.approved}
                hint={t('unavailability_reviews.stats_approved_desc')}
                icon={CircleCheck}
                accent="text-emerald-500"
            />
            <StatCard
                title={t('unavailability_reviews.stats_rejected')}
                value={stats.rejected}
                hint={t('unavailability_reviews.stats_rejected_desc')}
                icon={CircleX}
                accent="text-rose-500"
            />
        </div>
    );
}
