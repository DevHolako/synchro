import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/i18n/LanguageContext';
import { FIELD_CLASS } from '@/lib/form-classes';
import { store, update } from '@/routes/exam-periods';
import type { ExamPeriod, ExamSessionType } from './types';

const SESSION_TYPES: ExamSessionType[] = ['normal', 'rattrapage'];

interface ExamPeriodDialogProps {
    /** The period to edit, or `null` for a new one. */
    period: ExamPeriod | null;
    onClose: () => void;
}

/** Mounted per opening (keyed by the page), so the form starts from the period it edits. */
export function ExamPeriodDialog({ period, onClose }: ExamPeriodDialogProps) {
    const { t } = useTranslation();
    const form = useForm({
        name: period?.name ?? '',
        session_type: period?.session_type ?? ('normal' as ExamSessionType),
        academic_year: period?.academic_year ?? '',
        start_date: period?.start_date ?? '',
        end_date: period?.end_date ?? '',
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };

        if (period) {
            form.put(update.url(period.id), options);
        } else {
            form.post(store.url(), options);
        }
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-md">
                <form onSubmit={handleSubmit} className="grid gap-4">
                    <DialogHeader>
                        <DialogTitle>
                            {t(
                                period
                                    ? 'exams.edit_period'
                                    : 'exams.new_period',
                            )}
                        </DialogTitle>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor="period_name">{t('common.name')}</Label>
                        <Input
                            id="period_name"
                            value={form.data.name}
                            onChange={(e) =>
                                form.setData('name', e.target.value)
                            }
                            placeholder={t('exams.period_name_placeholder')}
                            required
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div className="grid gap-2">
                            <Label htmlFor="session_type">
                                {t('exams.session_type')}
                            </Label>
                            <select
                                id="session_type"
                                value={form.data.session_type}
                                onChange={(e) =>
                                    form.setData(
                                        'session_type',
                                        e.target.value as ExamSessionType,
                                    )
                                }
                                className={FIELD_CLASS}
                            >
                                {SESSION_TYPES.map((type) => (
                                    <option key={type} value={type}>
                                        {t(`exams.session_type_${type}`)}
                                    </option>
                                ))}
                            </select>
                            <InputError message={form.errors.session_type} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="academic_year">
                                {t('exams.academic_year')}
                            </Label>
                            <Input
                                id="academic_year"
                                value={form.data.academic_year}
                                onChange={(e) =>
                                    form.setData(
                                        'academic_year',
                                        e.target.value,
                                    )
                                }
                                placeholder={t(
                                    'exams.academic_year_placeholder',
                                )}
                                required
                            />
                            <InputError message={form.errors.academic_year} />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div className="grid gap-2">
                            <Label htmlFor="start_date">
                                {t('exams.start_date')}
                            </Label>
                            <Input
                                id="start_date"
                                type="date"
                                value={form.data.start_date}
                                onChange={(e) =>
                                    form.setData('start_date', e.target.value)
                                }
                                required
                            />
                            <InputError message={form.errors.start_date} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="end_date">
                                {t('exams.end_date')}
                            </Label>
                            <Input
                                id="end_date"
                                type="date"
                                min={form.data.start_date || undefined}
                                value={form.data.end_date}
                                onChange={(e) =>
                                    form.setData('end_date', e.target.value)
                                }
                                required
                            />
                            <InputError message={form.errors.end_date} />
                        </div>
                    </div>

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
