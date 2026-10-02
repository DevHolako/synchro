import { Archive, CalendarPlus, Pencil, Send, Trash2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';
import { FIELD_CLASS } from '@/lib/form-classes';
import { formatDay } from '@/pages/timetable/components/wall-clock-format';
import type { ExamConfirmation, ExamPeriod } from './types';

interface ExamPeriodBarProps {
    periods: ExamPeriod[];
    period: ExamPeriod | null;
    canManage: boolean;
    onSelect: (periodId: number) => void;
    onCreate: () => void;
    onEdit: (period: ExamPeriod) => void;
    onConfirm: (confirmation: ExamConfirmation) => void;
}

export function ExamPeriodBar({
    periods,
    period,
    canManage,
    onSelect,
    onCreate,
    onEdit,
    onConfirm,
}: ExamPeriodBarProps) {
    const { t, locale } = useTranslation();

    return (
        <div className="flex flex-wrap items-center gap-3 rounded-lg border border-neutral-200 bg-white p-4 shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
            {periods.length > 0 ? (
                <div className="w-full sm:w-[280px]">
                    <select
                        aria-label={t('exams.period')}
                        value={period?.id ?? ''}
                        onChange={(e) => onSelect(Number(e.target.value))}
                        className={FIELD_CLASS}
                    >
                        {periods.map((item) => (
                            <option key={item.id} value={item.id}>
                                {item.name} · {item.academic_year}
                            </option>
                        ))}
                    </select>
                </div>
            ) : null}

            {period ? (
                <div className="flex flex-wrap items-center gap-2 text-sm text-neutral-500">
                    <Badge variant="outline">
                        {t(`exams.period_status_${period.status}`)}
                    </Badge>
                    <Badge variant="secondary">
                        {t(`exams.session_type_${period.session_type}`)}
                    </Badge>
                    <span>
                        {t('exams.period_dates', {
                            start: formatDay(
                                period.start_date,
                                locale,
                                'medium',
                            ),
                            end: formatDay(period.end_date, locale, 'medium'),
                        })}
                    </span>
                </div>
            ) : null}

            {canManage ? (
                <div className="flex flex-wrap gap-2 sm:ml-auto">
                    {period ? (
                        <>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => onEdit(period)}
                            >
                                <Pencil className="mr-1.5 size-4" />
                                {t('exams.edit_period')}
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    onConfirm({
                                        kind: 'publish_period',
                                        period,
                                    })
                                }
                            >
                                <Send className="mr-1.5 size-4" />
                                {t('exams.publish_period')}
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    onConfirm({
                                        kind: 'archive_period',
                                        period,
                                    })
                                }
                            >
                                <Archive className="mr-1.5 size-4" />
                                {t('exams.archive_period')}
                            </Button>
                            {period.exams_count === 0 ? (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    aria-label={t('exams.delete_period')}
                                    onClick={() =>
                                        onConfirm({
                                            kind: 'delete_period',
                                            period,
                                        })
                                    }
                                >
                                    <Trash2 className="size-4" />
                                </Button>
                            ) : null}
                        </>
                    ) : null}
                    <Button size="sm" onClick={onCreate}>
                        <CalendarPlus className="mr-1.5 size-4" />
                        {t('exams.new_period')}
                    </Button>
                </div>
            ) : null}
        </div>
    );
}
