import { Check, X } from 'lucide-react';
import { memo } from 'react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';
import {
    UnavailabilityStatusBadge,
    UnavailabilityTypeBadge,
} from '@/components/unavailabilities/unavailability-badges';
import {
    describePeriod,
    describeSlot,
} from '@/components/unavailabilities/unavailability-format';
import { UnavailabilityReviewNote } from '@/components/unavailabilities/unavailability-review-note';
import type { ReviewableUnavailability, ReviewDecision } from './types';

interface ReviewRowProps {
    unavailability: ReviewableUnavailability;
    onReview: (
        unavailability: ReviewableUnavailability,
        decision: ReviewDecision,
    ) => void;
}

export const ReviewRow = memo(function ReviewRow({
    unavailability,
    onReview,
}: ReviewRowProps) {
    const { t, locale } = useTranslation();

    return (
        <tr className="align-top transition-colors hover:bg-neutral-50/50 dark:hover:bg-neutral-800/40">
            <td className="px-6 py-4">
                <div className="font-semibold text-neutral-900 dark:text-neutral-100">
                    {unavailability.teacher.name}
                </div>
                <div className="font-mono text-xs text-neutral-500">
                    {unavailability.teacher.email}
                </div>
            </td>
            <td className="px-6 py-4">
                <UnavailabilityTypeBadge type={unavailability.type} />
                <div className="mt-1 font-medium text-neutral-900 dark:text-neutral-100">
                    {describeSlot(unavailability, t)}
                </div>
                <div className="text-xs text-neutral-500">
                    {describePeriod(unavailability, t, locale)}
                </div>
            </td>
            <td className="max-w-xs px-6 py-4 text-neutral-600 dark:text-neutral-300">
                {unavailability.reason}
            </td>
            <td className="px-6 py-4">
                <UnavailabilityStatusBadge status={unavailability.status} />
                <UnavailabilityReviewNote unavailability={unavailability} />
            </td>
            <td className="px-6 py-4 text-right">
                {unavailability.status === 'pending' && (
                    <div className="flex items-center justify-end gap-1">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => onReview(unavailability, 'approved')}
                        >
                            <Check className="mr-1 size-4" />
                            {t('unavailability_reviews.approve')}
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => onReview(unavailability, 'rejected')}
                        >
                            <X className="mr-1 size-4" />
                            {t('unavailability_reviews.reject')}
                        </Button>
                    </div>
                )}
            </td>
        </tr>
    );
});
