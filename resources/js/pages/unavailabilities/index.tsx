import { Head, router, setLayoutProps } from '@inertiajs/react';
import { CalendarPlus } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';
import { dashboard } from '@/routes';
import { index } from '@/routes/unavailabilities';
import type { Unavailability } from '@/components/unavailabilities/types';
import type {
    UnavailabilityFilters,
    UnavailabilityPeriod,
} from './components/types';
import { UnavailabilitiesTable } from './components/unavailabilities-table';
import { UnavailabilityDialog } from './components/unavailability-dialog';
import { UnavailabilityFilterBar } from './components/unavailability-filter-bar';
import { WithdrawUnavailabilityDialog } from './components/withdraw-unavailability-dialog';

interface UnavailabilitiesIndexProps {
    unavailabilities: Unavailability[];
    filters: UnavailabilityFilters;
}

export default function UnavailabilitiesIndex({
    unavailabilities,
    filters,
}: UnavailabilitiesIndexProps) {
    const { t } = useTranslation();
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [editing, setEditing] = useState<Unavailability | null>(null);
    const [withdrawing, setWithdrawing] = useState<Unavailability | null>(null);

    useEffect(() => {
        setLayoutProps({
            breadcrumbs: [
                { title: t('nav.dashboard'), href: dashboard().url },
                { title: t('nav.unavailabilities'), href: index().url },
            ],
        });
    }, [t]);

    const handleCreate = () => {
        setEditing(null);
        setIsDialogOpen(true);
    };

    const handleEdit = useCallback((unavailability: Unavailability) => {
        setEditing(unavailability);
        setIsDialogOpen(true);
    }, []);

    const handlePeriodChange = (period: UnavailabilityPeriod) =>
        router.get(index.url(), period === 'past' ? { period } : {}, {
            preserveState: true,
        });

    return (
        <>
            <Head title={t('unavailabilities.title')} />

            <div className="flex h-full w-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                            {t('unavailabilities.title')}
                        </h1>
                        <p className="max-w-2xl text-sm text-neutral-500 dark:text-neutral-400">
                            {t('unavailabilities.description')}
                        </p>
                    </div>

                    <Button size="sm" onClick={handleCreate}>
                        <CalendarPlus className="mr-1.5 size-4" />
                        {t('unavailabilities.new')}
                    </Button>
                </div>

                <UnavailabilityFilterBar
                    period={filters.period}
                    onChange={handlePeriodChange}
                />

                <UnavailabilitiesTable
                    unavailabilities={unavailabilities}
                    onEdit={handleEdit}
                    onWithdraw={setWithdrawing}
                />
            </div>

            <UnavailabilityDialog
                key={editing?.id ?? 'new'}
                open={isDialogOpen}
                onOpenChange={setIsDialogOpen}
                unavailability={editing}
            />

            <WithdrawUnavailabilityDialog
                unavailability={withdrawing}
                onClose={() => setWithdrawing(null)}
            />
        </>
    );
}
