import { Pencil, Trash2 } from 'lucide-react';
import { memo } from 'react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';
import {
    UnavailabilityStatusBadge,
    UnavailabilityTypeBadge,
} from './unavailability-badges';
import { describePeriod, describeSlot } from './unavailability-format';
import { UnavailabilityReviewNote } from './unavailability-review-note';
import type { Unavailability } from './types';

interface UnavailabilityRowProps {
    unavailability: Unavailability;
    onEdit: (unavailability: Unavailability) => void;
    onWithdraw: (unavailability: Unavailability) => void;
}

export const UnavailabilityRow = memo(function UnavailabilityRow({
    unavailability,
    onEdit,
    onWithdraw,
}: UnavailabilityRowProps) {
    const { t, locale } = useTranslation();

    return (
        <tr className="align-top transition-colors hover:bg-neutral-50/50 dark:hover:bg-neutral-800/40">
            <td className="px-6 py-4">
                <UnavailabilityTypeBadge type={unavailability.type} />
            </td>
            <td className="px-6 py-4 font-medium text-neutral-900 dark:text-neutral-100">
                {describeSlot(unavailability, t)}
            </td>
            <td className="px-6 py-4 text-neutral-600 dark:text-neutral-300">
                {describePeriod(unavailability, t, locale)}
            </td>
            <td className="max-w-xs px-6 py-4 text-neutral-600 dark:text-neutral-300">
                {unavailability.reason}
            </td>
            <td className="px-6 py-4">
                <UnavailabilityStatusBadge status={unavailability.status} />
                <UnavailabilityReviewNote unavailability={unavailability} />
            </td>
            <td className="px-6 py-4 text-right">
                <div className="flex items-center justify-end gap-1">
                    {unavailability.status === 'pending' && (
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => onEdit(unavailability)}
                            title={t('unavailabilities.edit')}
                            aria-label={t('unavailabilities.edit')}
                        >
                            <Pencil className="size-4" />
                        </Button>
                    )}
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => onWithdraw(unavailability)}
                        title={t('unavailabilities.withdraw')}
                        aria-label={t('unavailabilities.withdraw')}
                    >
                        <Trash2 className="size-4" />
                    </Button>
                </div>
            </td>
        </tr>
    );
});
