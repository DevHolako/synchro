import { CalendarCheck, CalendarX, Pencil, Send, Trash2 } from 'lucide-react';
import { memo } from 'react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';
import {
    formatDay,
    timeOf,
} from '@/pages/timetable/components/wall-clock-format';
import { ExamStateBadge } from './exam-state-badge';
import type { Exam, ExamConfirmation } from './types';

interface ExamRowProps {
    exam: Exam;
    canManage: boolean;
    onEdit: (exam: Exam) => void;
    onConfirm: (confirmation: ExamConfirmation) => void;
}

export const ExamRow = memo(function ExamRow({
    exam,
    canManage,
    onEdit,
    onConfirm,
}: ExamRowProps) {
    const { t, locale } = useTranslation();
    const editable = exam.state === 'draft' || exam.state === 'scheduled';
    const upcoming = !exam.is_overdue;

    return (
        <tr className="align-top transition-colors hover:bg-neutral-50/50 dark:hover:bg-neutral-800/40">
            <td className="px-6 py-4">
                <div className="font-medium text-neutral-900 dark:text-neutral-100">
                    {formatDay(exam.start, locale, 'medium')}
                </div>
                <div className="text-xs text-neutral-500">
                    {timeOf(exam.start)}–{timeOf(exam.end)}
                </div>
            </td>
            <td className="px-6 py-4">
                <div className="flex items-center gap-2">
                    <span
                        className="size-2.5 shrink-0 rounded-full"
                        style={{ backgroundColor: exam.module.color_code }}
                    />
                    <span className="font-mono text-xs text-neutral-500">
                        {exam.module.code}
                    </span>
                </div>
                <div className="font-semibold text-neutral-900 dark:text-neutral-100">
                    {exam.module.name}
                </div>
            </td>
            <td className="px-6 py-4 text-neutral-600 dark:text-neutral-300">
                {exam.groups.map((group) => group.name).join(', ')}
            </td>
            <td className="px-6 py-4">
                <ExamStateBadge state={exam.state} overdue={exam.is_overdue} />
            </td>
            {canManage ? (
                <td className="px-6 py-4">
                    <div className="flex items-center justify-end gap-1">
                        {exam.state === 'draft' && upcoming ? (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    onConfirm({ kind: 'schedule', exam })
                                }
                            >
                                <CalendarCheck className="mr-1 size-4" />
                                {t('exams.schedule')}
                            </Button>
                        ) : null}
                        {exam.state === 'scheduled' && upcoming ? (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    onConfirm({ kind: 'publish', exam })
                                }
                            >
                                <Send className="mr-1 size-4" />
                                {t('exams.publish')}
                            </Button>
                        ) : null}
                        {exam.state === 'scheduled' ? (
                            <Button
                                variant="ghost"
                                size="sm"
                                aria-label={t('exams.unschedule')}
                                title={t('exams.unschedule')}
                                onClick={() =>
                                    onConfirm({ kind: 'unschedule', exam })
                                }
                            >
                                <CalendarX className="size-4" />
                            </Button>
                        ) : null}
                        {editable ? (
                            <>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    aria-label={t('common.edit')}
                                    title={t('common.edit')}
                                    onClick={() => onEdit(exam)}
                                >
                                    <Pencil className="size-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    aria-label={t('common.delete')}
                                    title={t('common.delete')}
                                    onClick={() =>
                                        onConfirm({ kind: 'delete', exam })
                                    }
                                >
                                    <Trash2 className="size-4" />
                                </Button>
                            </>
                        ) : null}
                    </div>
                </td>
            ) : null}
        </tr>
    );
});
