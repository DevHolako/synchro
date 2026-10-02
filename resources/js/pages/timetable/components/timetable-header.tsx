import { CalendarPlus, CalendarSync } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';

interface TimetableHeaderProps {
    canBrowse: boolean;
    canSchedule: boolean;
    onSchedule: () => void;
    onSubscribe: () => void;
}

export function TimetableHeader({
    canBrowse,
    canSchedule,
    onSchedule,
    onSubscribe,
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
            <div className="flex flex-wrap gap-2">
                <Button type="button" variant="outline" onClick={onSubscribe}>
                    <CalendarSync className="size-4" />
                    {t('calendar_feed.open_button')}
                </Button>
                {canSchedule ? (
                    <Button type="button" onClick={onSchedule}>
                        <CalendarPlus className="size-4" />
                        {t('schedule.open_button')}
                    </Button>
                ) : null}
            </div>
        </div>
    );
}
