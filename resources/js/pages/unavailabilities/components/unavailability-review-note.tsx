import { useTranslation } from '@/i18n/LanguageContext';
import type { Unavailability } from './types';

/** The reviewer's name and note, shown once a request has been decided. */
export function UnavailabilityReviewNote({
    unavailability,
}: {
    unavailability: Unavailability;
}) {
    const { t } = useTranslation();

    if (unavailability.status === 'pending') {
        return null;
    }

    return (
        <div className="mt-1 space-y-0.5 text-xs text-neutral-500">
            {unavailability.reviewer && (
                <div>
                    {t('unavailabilities.reviewed_by', {
                        name: unavailability.reviewer.name,
                    })}
                </div>
            )}
            {unavailability.review_note && (
                <div className="italic">
                    {t('unavailabilities.review_note', {
                        note: unavailability.review_note,
                    })}
                </div>
            )}
        </div>
    );
}
