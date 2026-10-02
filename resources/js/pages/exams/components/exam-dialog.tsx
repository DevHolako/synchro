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
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/i18n/LanguageContext';
import { moduleLabel } from '@/lib/module-label';
import { FIELD_CLASS } from '@/lib/form-classes';
import { timeOf } from '@/pages/timetable/components/wall-clock-format';
import { store, update } from '@/routes/exams';
import { ExamConflictWarnings } from './exam-conflict-warnings';
import { ExamTimeFields } from './exam-time-fields';
import type { Exam, ExamOptions, ExamPeriod } from './types';
import { useExamCheck } from './use-exam-check';

interface ExamDialogProps {
    period: ExamPeriod;
    /** The exam to edit, or `null` for a new draft. */
    exam: Exam | null;
    options: ExamOptions;
    onClose: () => void;
}

/** Mounted per opening (keyed by the page), so the form starts from the exam it edits. */
export function ExamDialog({
    period,
    exam,
    options,
    onClose,
}: ExamDialogProps) {
    const { t } = useTranslation();
    const form = useForm({
        module_id: exam ? String(exam.module.id) : '',
        student_group_ids: exam?.groups.map((group) => group.id) ?? [],
        date: exam?.start.slice(0, 10) ?? period.start_date,
        start: exam ? timeOf(exam.start) : '09:00',
        end: exam ? timeOf(exam.end) : '11:00',
    });
    const errors = form.errors as Record<string, string | undefined>;
    const { module_id, student_group_ids, date, start, end } = form.data;
    const programId = options.modules.find(
        (module) => module.id === Number(module_id),
    )?.program_id;
    const groups = options.groups.filter(
        (group) => group.program_id === programId,
    );
    const complete =
        module_id !== '' &&
        student_group_ids.length > 0 &&
        date !== '' &&
        start < end;
    const conflicts = useExamCheck(
        complete
            ? {
                  exam_period_id: period.id,
                  module_id,
                  student_group_ids,
                  starts_at: `${date} ${start}`,
                  ends_at: `${date} ${end}`,
              }
            : null,
        exam?.id ?? null,
    );

    const toggleGroup = (groupId: number, checked: boolean) =>
        form.setData(
            'student_group_ids',
            checked
                ? [...student_group_ids, groupId]
                : student_group_ids.filter((id) => id !== groupId),
        );

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        form.transform((data) => ({
            exam_period_id: period.id,
            module_id: data.module_id,
            student_group_ids: data.student_group_ids,
            starts_at: `${data.date} ${data.start}`,
            ends_at: `${data.date} ${data.end}`,
        }));
        const visit = { preserveScroll: true, onSuccess: onClose };

        if (exam) {
            form.put(update.url(exam.id), visit);
        } else {
            form.post(store.url(), visit);
        }
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-lg">
                <form onSubmit={handleSubmit} className="grid gap-4">
                    <DialogHeader>
                        <DialogTitle>
                            {t(exam ? 'exams.edit_exam' : 'exams.new_exam')}
                        </DialogTitle>
                        <DialogDescription>
                            {t(
                                exam?.state === 'scheduled'
                                    ? 'exams.dialog_desc_scheduled'
                                    : 'exams.dialog_desc_draft',
                            )}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor="module_id">
                            {t('exams.col_module')}
                        </Label>
                        <select
                            id="module_id"
                            value={module_id}
                            onChange={(e) => {
                                form.setData((data) => ({
                                    ...data,
                                    module_id: e.target.value,
                                    student_group_ids: [],
                                }));
                            }}
                            className={FIELD_CLASS}
                            required
                        >
                            <option value="">{t('exams.pick_module')}</option>
                            {options.modules.map((module) => (
                                <option key={module.id} value={module.id}>
                                    {moduleLabel(module)}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.module_id} />
                    </div>

                    <fieldset className="grid gap-2">
                        <legend className="mb-2 text-sm font-medium">
                            {t('exams.col_groups')}
                        </legend>
                        {groups.length === 0 ? (
                            <p className="text-xs text-neutral-500">
                                {t('exams.pick_module_first')}
                            </p>
                        ) : (
                            <div className="grid max-h-40 grid-cols-2 gap-2 overflow-y-auto">
                                {groups.map((group) => (
                                    <label
                                        key={group.id}
                                        className="flex items-center gap-2 text-sm"
                                    >
                                        <Checkbox
                                            checked={student_group_ids.includes(
                                                group.id,
                                            )}
                                            onCheckedChange={(checked) =>
                                                toggleGroup(
                                                    group.id,
                                                    checked === true,
                                                )
                                            }
                                        />
                                        {group.name}
                                    </label>
                                ))}
                            </div>
                        )}
                        <InputError
                            message={
                                errors.student_group_ids ??
                                errors['student_group_ids.0']
                            }
                        />
                    </fieldset>

                    <ExamTimeFields
                        date={date}
                        start={start}
                        end={end}
                        minDate={period.start_date}
                        maxDate={period.end_date}
                        onChange={(field, value) => form.setData(field, value)}
                    />
                    <InputError
                        message={
                            errors.starts_at ?? errors.ends_at ?? errors.exam
                        }
                    />
                    <InputError message={errors.conflicts} />

                    <ExamConflictWarnings conflicts={conflicts} />

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            {t('common.save')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
