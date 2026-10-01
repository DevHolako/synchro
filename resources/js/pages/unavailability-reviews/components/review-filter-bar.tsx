import { Filter, RotateCcw } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';
import {
    UNAVAILABILITY_STATUSES,
    UNAVAILABILITY_TYPES,
} from '@/pages/unavailabilities/components/types';
import type { ReviewFilters, Teacher } from './types';

const SELECT_CLASS =
    'w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-800 dark:bg-neutral-900';

interface ReviewFilterBarProps {
    filters: ReviewFilters;
    teachers: Teacher[];
    onChange: (patch: Partial<ReviewFilters>) => void;
    onApply: () => void;
    onReset: () => void;
}

export function ReviewFilterBar({
    filters,
    teachers,
    onChange,
    onApply,
    onReset,
}: ReviewFilterBarProps) {
    const { t } = useTranslation();

    return (
        <div className="flex flex-wrap items-center gap-3 rounded-lg border border-neutral-200 bg-white p-4 shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
            <div className="w-[170px]">
                <select
                    aria-label={t('common.status')}
                    value={filters.status}
                    onChange={(e) =>
                        onChange({
                            status: e.target.value as ReviewFilters['status'],
                        })
                    }
                    className={SELECT_CLASS}
                >
                    {UNAVAILABILITY_STATUSES.map((status) => (
                        <option key={status} value={status}>
                            {t(`unavailabilities.status_${status}`)}
                        </option>
                    ))}
                    <option value="all">
                        {t('unavailability_reviews.all_statuses')}
                    </option>
                </select>
            </div>

            <div className="w-[170px]">
                <select
                    aria-label={t('unavailabilities.col_type')}
                    value={filters.type}
                    onChange={(e) =>
                        onChange({
                            type: e.target.value as ReviewFilters['type'],
                        })
                    }
                    className={SELECT_CLASS}
                >
                    <option value="">
                        {t('unavailability_reviews.all_types')}
                    </option>
                    {UNAVAILABILITY_TYPES.map((type) => (
                        <option key={type} value={type}>
                            {t(`unavailabilities.type_${type}`)}
                        </option>
                    ))}
                </select>
            </div>

            <div className="min-w-[200px] flex-1 sm:max-w-[260px]">
                <select
                    aria-label={t('unavailabilities.col_teacher')}
                    value={filters.teacher_id}
                    onChange={(e) => onChange({ teacher_id: e.target.value })}
                    className={SELECT_CLASS}
                >
                    <option value="">
                        {t('unavailability_reviews.all_teachers')}
                    </option>
                    {teachers.map((teacher) => (
                        <option key={teacher.id} value={teacher.id}>
                            {teacher.name}
                        </option>
                    ))}
                </select>
            </div>

            <Button variant="default" size="sm" onClick={onApply}>
                <Filter className="mr-1.5 size-4" />
                {t('common.apply_filters')}
            </Button>

            <Button variant="ghost" size="sm" onClick={onReset}>
                <RotateCcw className="mr-1.5 size-4" />
                {t('common.reset')}
            </Button>
        </div>
    );
}
