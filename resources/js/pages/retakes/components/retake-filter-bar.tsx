import { useTranslation } from '@/i18n/LanguageContext';
import { FIELD_CLASS } from '@/lib/form-classes';
import type { RetakePeriodOption } from './types';

interface RetakeFilterBarProps {
    periods: RetakePeriodOption[];
    periodId: number;
    onPeriodChange: (periodId: number) => void;
}

export function RetakeFilterBar({
    periods,
    periodId,
    onPeriodChange,
}: RetakeFilterBarProps) {
    const { t } = useTranslation();

    return (
        <select
            aria-label={t('retakes.period')}
            className={`${FIELD_CLASS} sm:max-w-sm`}
            value={periodId}
            onChange={(event) => onPeriodChange(Number(event.target.value))}
        >
            {periods.map((option) => (
                <option key={option.id} value={option.id}>
                    {option.name} · {option.academic_year}
                </option>
            ))}
        </select>
    );
}
