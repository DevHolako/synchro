import { Head, router, setLayoutProps } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import { useTranslation } from '@/i18n/LanguageContext';
import { dashboard } from '@/routes';
import { index } from '@/routes/unavailability-reviews';
import { ReviewDialog } from './components/review-dialog';
import { ReviewFilterBar } from './components/review-filter-bar';
import { ReviewStatsCards } from './components/review-stats';
import { ReviewsTable } from './components/reviews-table';
import type {
    PaginatedUnavailabilities,
    PendingReview,
    ReviewableUnavailability,
    ReviewDecision,
    ReviewFilters,
    ReviewStats,
    Teacher,
} from './components/types';

interface UnavailabilityReviewsIndexProps {
    unavailabilities: PaginatedUnavailabilities;
    teachers: Teacher[];
    filters: ReviewFilters;
    stats: ReviewStats;
}

const DEFAULT_FILTERS: ReviewFilters = {
    status: 'pending',
    type: '',
    teacher_id: '',
};

function compactFilters(filters: ReviewFilters) {
    return Object.fromEntries(
        Object.entries(filters).filter(
            ([key, value]) =>
                value !== '' && !(key === 'status' && value === 'pending'),
        ),
    );
}

export default function UnavailabilityReviewsIndex({
    unavailabilities,
    teachers,
    filters: initialFilters,
    stats,
}: UnavailabilityReviewsIndexProps) {
    const { t } = useTranslation();
    const [filters, setFilters] = useState<ReviewFilters>(initialFilters);
    const [review, setReview] = useState<PendingReview | null>(null);

    useEffect(() => {
        setLayoutProps({
            breadcrumbs: [
                { title: t('nav.dashboard'), href: dashboard().url },
                { title: t('nav.unavailability_reviews'), href: index().url },
            ],
        });
    }, [t]);

    const applyFilters = (next: ReviewFilters) =>
        router.get(index.url(), compactFilters(next), { preserveState: true });

    const handleReset = () => {
        setFilters(DEFAULT_FILTERS);
        applyFilters(DEFAULT_FILTERS);
    };

    const handleReview = useCallback(
        (unavailability: ReviewableUnavailability, decision: ReviewDecision) =>
            setReview({ unavailability, decision }),
        [],
    );

    return (
        <>
            <Head title={t('unavailability_reviews.title')} />

            <div className="flex h-full w-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                        {t('unavailability_reviews.title')}
                    </h1>
                    <p className="max-w-2xl text-sm text-neutral-500 dark:text-neutral-400">
                        {t('unavailability_reviews.description')}
                    </p>
                </div>

                <ReviewStatsCards stats={stats} />

                <ReviewFilterBar
                    filters={filters}
                    teachers={teachers}
                    onChange={(patch) =>
                        setFilters((current) => ({ ...current, ...patch }))
                    }
                    onApply={() => applyFilters(filters)}
                    onReset={handleReset}
                />

                <ReviewsTable
                    unavailabilities={unavailabilities}
                    onReview={handleReview}
                />
            </div>

            <ReviewDialog review={review} onClose={() => setReview(null)} />
        </>
    );
}
