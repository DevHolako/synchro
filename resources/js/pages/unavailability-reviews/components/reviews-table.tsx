import { Link } from '@inertiajs/react';
import { CalendarClock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';
import { ReviewRow } from './review-row';
import type {
    PaginatedUnavailabilities,
    ReviewableUnavailability,
    ReviewDecision,
} from './types';

interface ReviewsTableProps {
    unavailabilities: PaginatedUnavailabilities;
    onReview: (
        unavailability: ReviewableUnavailability,
        decision: ReviewDecision,
    ) => void;
}

function PageLink({ href, label }: { href: string | null; label: string }) {
    if (!href) {
        return (
            <Button variant="outline" size="sm" disabled>
                {label}
            </Button>
        );
    }

    return (
        <Button variant="outline" size="sm" asChild>
            <Link href={href} preserveScroll>
                {label}
            </Link>
        </Button>
    );
}

export function ReviewsTable({
    unavailabilities,
    onReview,
}: ReviewsTableProps) {
    const { t } = useTranslation();

    if (unavailabilities.data.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-neutral-300 p-12 text-center dark:border-neutral-700">
                <CalendarClock className="size-12 text-neutral-400" />
                <h3 className="mt-4 text-base font-semibold text-neutral-900 dark:text-neutral-100">
                    {t('unavailability_reviews.empty_title')}
                </h3>
                <p className="mt-1 text-sm text-neutral-500">
                    {t('unavailability_reviews.empty_desc')}
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
                            {t('unavailabilities.col_teacher')}
                        </th>
                        <th className="px-6 py-3">
                            {t('unavailabilities.col_when')}
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
                    {unavailabilities.data.map((unavailability) => (
                        <ReviewRow
                            key={unavailability.id}
                            unavailability={unavailability}
                            onReview={onReview}
                        />
                    ))}
                </tbody>
            </table>

            <div className="flex items-center justify-between border-t border-neutral-200 px-6 py-3 text-xs text-neutral-500 dark:border-neutral-800">
                <span>
                    {t('unavailability_reviews.pagination_summary', {
                        from: unavailabilities.from ?? 0,
                        to: unavailabilities.to ?? 0,
                        total: unavailabilities.total,
                    })}
                </span>
                <div className="flex gap-2">
                    <PageLink
                        href={unavailabilities.prev_page_url}
                        label={t('unavailability_reviews.previous')}
                    />
                    <PageLink
                        href={unavailabilities.next_page_url}
                        label={t('unavailability_reviews.next')}
                    />
                </div>
            </div>
        </div>
    );
}
