import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/i18n/LanguageContext';
import { FIELD_CLASS } from '@/lib/form-classes';
import { timeOf } from '@/pages/timetable/components/wall-clock-format';
import { emergencyReschedule } from '@/routes/exams';
import { ExamTimeFields } from './exam-time-fields';
import { RescheduleRoomPicker } from './reschedule-room-picker';
import type { Exam, ExamPeriod } from './types';

interface ExamRescheduleDialogProps {
    exam: Exam;
    period: ExamPeriod;
    onClose: () => void;
}

/** The emergency reschedule of a published exam (ADR 0005): every convocation is replaced. */
export function ExamRescheduleDialog({
    exam,
    period,
    onClose,
}: ExamRescheduleDialogProps) {
    const { t } = useTranslation();
    const form = useForm({
        date: exam.start.slice(0, 10),
        start: timeOf(exam.start),
        end: timeOf(exam.end),
        change_rooms: false,
        room_ids: [] as number[],
        reason: '',
        confirmed: false,
    });
    const errors = form.errors as Record<string, string | undefined>;

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        form.transform((data) => ({
            starts_at: `${data.date} ${data.start}`,
            ends_at: `${data.date} ${data.end}`,
            room_ids: data.change_rooms ? data.room_ids : null,
            reason: data.reason,
            confirmed: data.confirmed,
        }));
        form.post(emergencyReschedule.url(exam.id), {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-lg">
                <form onSubmit={handleSubmit} className="grid gap-4">
                    <DialogHeader>
                        <DialogTitle>{t('exams.reschedule_title')}</DialogTitle>
                        <DialogDescription>
                            {exam.module.code} · {exam.module.name}
                        </DialogDescription>
                    </DialogHeader>

                    <ExamTimeFields
                        date={form.data.date}
                        start={form.data.start}
                        end={form.data.end}
                        minDate={period.start_date}
                        maxDate={period.end_date}
                        onChange={(field, value) => form.setData(field, value)}
                    />
                    <InputError
                        message={
                            errors.starts_at ??
                            errors.ends_at ??
                            errors.exam ??
                            errors.conflicts
                        }
                    />

                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox
                            checked={form.data.change_rooms}
                            onCheckedChange={(checked) =>
                                form.setData('change_rooms', checked === true)
                            }
                        />
                        {t('exams.reschedule_change_rooms')}
                    </label>
                    {form.data.change_rooms ? (
                        <RescheduleRoomPicker
                            examId={exam.id}
                            roomIds={form.data.room_ids}
                            onChange={(roomIds) =>
                                form.setData('room_ids', roomIds)
                            }
                        />
                    ) : null}
                    <InputError message={errors.room_ids} />

                    <textarea
                        aria-label={t('exams.reschedule_reason')}
                        placeholder={t('exams.reschedule_reason')}
                        rows={3}
                        maxLength={1000}
                        value={form.data.reason}
                        onChange={(e) => form.setData('reason', e.target.value)}
                        className={FIELD_CLASS}
                        required
                    />
                    <InputError message={errors.reason} />

                    <label className="flex items-start gap-2 rounded-md border border-rose-300 bg-rose-50 p-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-200">
                        <Checkbox
                            className="mt-0.5"
                            checked={form.data.confirmed}
                            onCheckedChange={(checked) =>
                                form.setData('confirmed', checked === true)
                            }
                        />
                        {t('exams.reschedule_confirm')}
                    </label>
                    <InputError message={errors.confirmed} />

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button
                            type="submit"
                            variant="destructive"
                            disabled={form.processing || !form.data.confirmed}
                        >
                            {form.processing && <Spinner />}
                            {t('exams.reschedule_action')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
