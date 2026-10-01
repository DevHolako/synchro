import { CalendarPlus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';

interface TimetableHeaderProps {
    canBrowse: boolean;
    canSchedule: boolean;
    onSchedule: () => void;
}

export function TimetableHeader({
    canBrowse,
    canSchedule,
    onSchedule,
}: TimetableHeaderProps) {
    const { t } = useTranslation();

    return (
        <div className="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                    {t('timetable.title')}
                </h1>
                <p className="max-w-2xl text-sm text-neutral-500 dark:text-neutral-400">
                    {canBrowse
                        ? t('timetable.description')
                        : t('timetable.my_description')}
                </p>
            </div>
            {canSchedule ? (
                <Button type="button" onClick={onSchedule}>
                    <CalendarPlus className="size-4" />
                    {t('schedule.open_button')}
                </Button>
            ) : null}
        </div>
    );
}
