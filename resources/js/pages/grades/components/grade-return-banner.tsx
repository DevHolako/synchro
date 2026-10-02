import { Undo2 } from 'lucide-react';
import { useTranslation } from '@/i18n/LanguageContext';
import {
    formatDay,
    timeOf,
} from '@/pages/timetable/components/wall-clock-format';

interface GradeReturnBannerProps {
    returnedAt: string;
    reason: string;
}

/** Why coordination sent the sheet back, shown until it is submitted again. */
export function GradeReturnBanner({
    returnedAt,
    reason,
}: GradeReturnBannerProps) {
    const { t, locale } = useTranslation();

    return (
        <div className="flex items-start gap-2 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
            <Undo2 className="mt-0.5 size-4 shrink-0" />
            <span>
                {t('grades.return_banner', {
                    date: `${formatDay(returnedAt, locale, 'medium')} ${timeOf(returnedAt)}`,
                    reason,
                })}
            </span>
        </div>
    );
}
