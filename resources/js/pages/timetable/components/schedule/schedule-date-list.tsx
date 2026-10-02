import { Plus, X } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/i18n/LanguageContext';
import { formatDay } from '../wall-clock-format';

interface ScheduleDateListProps {
    dates: string[];
    onRemove: (date: string) => void;
    onAdd: (date: string) => void;
}

export function ScheduleDateList({
    dates,
    onRemove,
    onAdd,
}: ScheduleDateListProps) {
    const { t, locale } = useTranslation();
    const [draft, setDraft] = useState('');

    const handleAdd = () => {
        if (draft !== '') {
            onAdd(draft);
            setDraft('');
        }
    };

    return (
        <div className="grid gap-2">
            <span className="text-sm font-semibold">
                {t('schedule.dates_title', { count: dates.length })}
            </span>
            <p className="text-xs text-neutral-500">
                {t('schedule.dates_hint')}
            </p>
            {dates.length === 0 ? (
                <p className="text-sm text-neutral-500">
                    {t('schedule.dates_empty')}
                </p>
            ) : (
                <ul className="flex max-h-32 flex-wrap gap-1.5 overflow-y-auto">
                    {dates.map((date) => (
                        <li
                            key={date}
                            className="flex items-center gap-1 rounded-md border border-neutral-200 py-0.5 pr-1 pl-2 text-xs dark:border-neutral-700"
                        >
                            {formatDay(date, locale, 'medium')}
                            <button
                                type="button"
                                onClick={() => onRemove(date)}
                                aria-label={t('schedule.remove_date', {
                                    date,
                                })}
                                className="rounded p-0.5 text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900 dark:hover:bg-neutral-800"
                            >
                                <X className="size-3" />
                            </button>
                        </li>
                    ))}
                </ul>
            )}
            <div className="flex items-center gap-2">
                <Input
                    type="date"
                    aria-label={t('schedule.add_date_label')}
                    value={draft}
                    onChange={(e) => setDraft(e.target.value)}
                    className="w-44"
                />
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={handleAdd}
                    disabled={draft === ''}
                >
                    <Plus className="size-4" />
                    {t('schedule.add_date')}
                </Button>
            </div>
        </div>
    );
}
