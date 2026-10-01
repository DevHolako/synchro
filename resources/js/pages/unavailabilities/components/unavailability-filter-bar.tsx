import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';
import type { UnavailabilityPeriod } from './types';

const PERIODS: readonly UnavailabilityPeriod[] = ['current', 'past'];

interface UnavailabilityFilterBarProps {
    period: UnavailabilityPeriod;
    onChange: (period: UnavailabilityPeriod) => void;
}

export function UnavailabilityFilterBar({
    period,
    onChange,
}: UnavailabilityFilterBarProps) {
    const { t } = useTranslation();

    return (
        <div className="flex gap-2">
            {PERIODS.map((value) => (
                <Button
                    key={value}
                    size="sm"
                    variant={value === period ? 'default' : 'outline'}
                    onClick={() => onChange(value)}
                >
                    {t(`unavailabilities.period_${value}`)}
                </Button>
            ))}
        </div>
    );
}
