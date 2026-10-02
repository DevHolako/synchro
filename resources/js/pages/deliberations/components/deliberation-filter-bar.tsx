import { useTranslation } from '@/i18n/LanguageContext';
import { FIELD_CLASS } from '@/lib/form-classes';
import { DELIBERATION_STATUSES } from './types';
import type { DeliberationPeriod } from './types';

interface DeliberationFilterBarProps {
    periods: DeliberationPeriod[];
    periodId: number | null;
    status: string;
    onChange: (filters: { period?: number; status?: string }) => void;
}

export function DeliberationFilterBar({
    periods,
    periodId,
    status,
    onChange,
}: DeliberationFilterBarProps) {
    const { t } = useTranslation();

    return (
        <div className="flex flex-col gap-3 sm:flex-row">
            <select
                aria-label={t('deliberations.period')}
                className={`${FIELD_CLASS} sm:max-w-sm`}
                value={periodId ?? ''}
                onChange={(event) =>
                    onChange({ period: Number(event.target.value), status })
                }
            >
                {periods.map((period) => (
                    <option key={period.id} value={period.id}>
                        {period.name} · {period.academic_year}
                        {period.session_type === 'rattrapage'
                            ? ` · ${t('deliberations.session_retake')}`
                            : ''}
                    </option>
                ))}
            </select>
            <select
                aria-label={t('deliberations.col_status')}
                className={`${FIELD_CLASS} sm:max-w-xs`}
                value={status}
                onChange={(event) =>
                    onChange({
                        period: periodId ?? undefined,
                        status: event.target.value,
                    })
                }
            >
                <option value="">{t('deliberations.all_statuses')}</option>
                {DELIBERATION_STATUSES.map((value) => (
                    <option key={value} value={value}>
                        {t(`deliberations.status_${value}`)}
                    </option>
                ))}
            </select>
        </div>
    );
}
