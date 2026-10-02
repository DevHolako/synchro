import { memo } from 'react';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/i18n/LanguageContext';
import { cn } from '@/lib/utils';
import { ATTENDANCE_STATUSES } from './types';
import type { AttendanceStatus, RosterStudent } from './types';

const PILL_ACTIVE: Record<AttendanceStatus, string> = {
    present: 'border-emerald-600 bg-emerald-600 text-white',
    absent: 'border-red-600 bg-red-600 text-white',
    late: 'border-amber-500 bg-amber-500 text-white',
    excused: 'border-sky-600 bg-sky-600 text-white',
};

interface AttendanceRowProps {
    student: RosterStudent;
    status: AttendanceStatus | null;
    remarks: string;
    onStatus: (studentId: number, status: AttendanceStatus) => void;
    onRemarks: (studentId: number, remarks: string) => void;
}

export const AttendanceRow = memo(function AttendanceRow({
    student,
    status,
    remarks,
    onStatus,
    onRemarks,
}: AttendanceRowProps) {
    const { t } = useTranslation();
    const percent =
        student.module_recorded > 0
            ? Math.round(
                  (student.module_absences / student.module_recorded) * 100,
              )
            : 0;

    return (
        <li className="grid gap-2 py-3">
            <div className="flex flex-wrap items-baseline justify-between gap-x-3">
                <span className="text-sm font-medium">{student.name}</span>
                <span className="text-xs text-neutral-500">
                    {[student.student_number, student.group]
                        .filter(Boolean)
                        .join(' · ')}
                </span>
            </div>
            <p
                className={cn(
                    'text-xs',
                    student.module_absences > 0
                        ? 'text-red-600 dark:text-red-400'
                        : 'text-neutral-500',
                )}
            >
                {student.module_absences > 0
                    ? t('attendance.absence_rate', {
                          absences: student.module_absences,
                          recorded: student.module_recorded,
                          percent,
                      })
                    : t('attendance.absence_none')}
            </p>
            <div className="flex flex-wrap gap-1.5">
                {ATTENDANCE_STATUSES.map((item) => (
                    <button
                        key={item}
                        type="button"
                        aria-pressed={status === item}
                        onClick={() => onStatus(student.student_id, item)}
                        className={cn(
                            'rounded-full border px-3 py-1 text-xs font-medium transition-colors',
                            status === item
                                ? PILL_ACTIVE[item]
                                : 'border-neutral-200 text-neutral-600 hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800',
                        )}
                    >
                        {t(`attendance.status_${item}`)}
                    </button>
                ))}
            </div>
            <Input
                value={remarks}
                maxLength={255}
                placeholder={t('attendance.remarks_placeholder')}
                aria-label={t('attendance.remarks_label', {
                    name: student.name,
                })}
                onChange={(e) => onRemarks(student.student_id, e.target.value)}
                className="h-8 text-xs"
            />
        </li>
    );
});
