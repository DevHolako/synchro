import { CalendarX2 } from 'lucide-react';
import { useTranslation } from '@/i18n/LanguageContext';
import type { Unavailability } from '@/components/unavailabilities/types';
import { UnavailabilityRow } from './unavailability-row';

interface UnavailabilitiesTableProps {
    unavailabilities: Unavailability[];
    onEdit: (unavailability: Unavailability) => void;
    onWithdraw: (unavailability: Unavailability) => void;
}

export function UnavailabilitiesTable({
    unavailabilities,
    onEdit,
    onWithdraw,
}: UnavailabilitiesTableProps) {
    const { t } = useTranslation();

    if (unavailabilities.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-neutral-300 p-12 text-center dark:border-neutral-700">
                <CalendarX2 className="size-12 text-neutral-400" />
                <h3 className="mt-4 text-base font-semibold text-neutral-900 dark:text-neutral-100">
                    {t('unavailabilities.empty_title')}
                </h3>
                <p className="mt-1 text-sm text-neutral-500">
                    {t('unavailabilities.empty_desc')}
                </p>
            </div>
        );
    }

    return (
        <div className="overflow-x-auto rounded-lg border border-neutral-200 bg-white shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
            <table className="w-full text-left text-sm">
                <thead className="border-b border-neutral-200 bg-neutral-50/50 text-xs font-medium text-neutral-500 dark:border-neutral-800 dark:bg-neutral-800/50 dark:text-neutral-400">
                    <tr>
                        <th className="px-6 py-3">
                            {t('unavailabilities.col_type')}
                        </th>
                        <th className="px-6 py-3">
                            {t('unavailabilities.col_when')}
                        </th>
                        <th className="px-6 py-3">
                            {t('unavailabilities.col_period')}
                        </th>
                        <th className="px-6 py-3">
                            {t('unavailabilities.col_reason')}
                        </th>
                        <th className="px-6 py-3">{t('common.status')}</th>
                        <th className="px-6 py-3 text-right">
                            {t('common.actions')}
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                    {unavailabilities.map((unavailability) => (
                        <UnavailabilityRow
                            key={unavailability.id}
                            unavailability={unavailability}
                            onEdit={onEdit}
                            onWithdraw={onWithdraw}
                        />
                    ))}
                </tbody>
            </table>
        </div>
    );
}
