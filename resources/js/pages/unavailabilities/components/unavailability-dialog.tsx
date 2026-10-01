import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/i18n/LanguageContext';
import { store, update } from '@/routes/unavailabilities';
import type {
    Unavailability,
    UnavailabilityType,
} from '@/components/unavailabilities/types';
import {
    UNAVAILABILITY_TYPES,
    WEEKDAYS,
} from '@/components/unavailabilities/types';

const FIELD_CLASS =
    'w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-800 dark:bg-neutral-900';

interface UnavailabilityFormData {
    type: UnavailabilityType;
    day_of_week: string;
    start_date: string;
    end_date: string;
    start_time: string;
    end_time: string;
    reason: string;
}

function todayIso(): string {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${day}`;
}

function initialData(
    unavailability: Unavailability | null,
): UnavailabilityFormData {
    return {
        type: unavailability?.type ?? 'recurring_weekly',
        day_of_week: String(unavailability?.day_of_week ?? 1),
        start_date: unavailability?.start_date ?? todayIso(),
        end_date: unavailability?.end_date ?? '',
        start_time: unavailability?.start_time ?? '',
        end_time: unavailability?.end_time ?? '',
        reason: unavailability?.reason ?? '',
    };
}

function toPayload(data: UnavailabilityFormData) {
    const recurring = data.type === 'recurring_weekly';

    return {
        ...data,
        day_of_week: recurring ? Number(data.day_of_week) : null,
        end_date: data.end_date || null,
        start_time: data.start_time || null,
        end_time: data.end_time || null,
    };
}

interface UnavailabilityDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** The pending unavailability being edited, or null to declare a new one. */
    unavailability: Unavailability | null;
}

/** Mount with a `key` per edited record so the form starts from that record's values. */
export function UnavailabilityDialog({
    open,
    onOpenChange,
    unavailability,
}: UnavailabilityDialogProps) {
    const { t } = useTranslation();
    const form = useForm(initialData(unavailability));
    const recurring = form.data.type === 'recurring_weekly';

    const handleOpenChange = (next: boolean) => {
        if (!next) {
            form.reset();
            form.clearErrors();
        }
        onOpenChange(next);
    };

    const handleStartDateChange = (value: string) => {
        form.setData((data) => ({
            ...data,
            start_date: value,
            end_date:
                !recurring && (!data.end_date || data.end_date < value)
                    ? value
                    : data.end_date,
        }));
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        form.transform(toPayload);

        const options = {
            preserveScroll: true,
            onSuccess: () => handleOpenChange(false),
        };

        if (unavailability) {
            form.put(update.url(unavailability.id), options);
        } else {
            form.post(store.url(), options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="sm:max-w-[520px]">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {t(
                                unavailability
                                    ? 'unavailabilities.dialog_edit_title'
                                    : 'unavailabilities.dialog_create_title',
                            )}
                        </DialogTitle>
                        <DialogDescription>
                            {t('unavailabilities.dialog_desc')}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="unavailability_type">
                                    {t('unavailabilities.field_type')}
                                </Label>
                                <select
                                    id="unavailability_type"
                                    value={form.data.type}
                                    onChange={(e) =>
                                        form.setData(
                                            'type',
                                            e.target
                                                .value as UnavailabilityType,
                                        )
                                    }
                                    className={FIELD_CLASS}
                                >
                                    {UNAVAILABILITY_TYPES.map((type) => (
                                        <option key={type} value={type}>
                                            {t(`unavailabilities.type_${type}`)}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={form.errors.type} />
                            </div>

                            {recurring && (
                                <div className="grid gap-2">
                                    <Label htmlFor="unavailability_day">
                                        {t('unavailabilities.field_day')}
                                    </Label>
                                    <select
                                        id="unavailability_day"
                                        value={form.data.day_of_week}
                                        onChange={(e) =>
                                            form.setData(
                                                'day_of_week',
                                                e.target.value,
                                            )
                                        }
                                        className={FIELD_CLASS}
                                    >
                                        {WEEKDAYS.map((day) => (
                                            <option key={day} value={day}>
                                                {t(
                                                    `unavailabilities.day_${day}`,
                                                )}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError
                                        message={form.errors.day_of_week}
                                    />
                                </div>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="unavailability_start_date">
                                    {t('unavailabilities.field_start_date')}
                                </Label>
                                <Input
                                    id="unavailability_start_date"
                                    type="date"
                                    min={todayIso()}
                                    value={form.data.start_date}
                                    onChange={(e) =>
                                        handleStartDateChange(e.target.value)
                                    }
                                    required
                                />
                                <InputError message={form.errors.start_date} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="unavailability_end_date">
                                    {t('unavailabilities.field_end_date')}
                                </Label>
                                <Input
                                    id="unavailability_end_date"
                                    type="date"
                                    min={form.data.start_date}
                                    value={form.data.end_date}
                                    onChange={(e) =>
                                        form.setData('end_date', e.target.value)
                                    }
                                    required={!recurring}
                                />
                                <InputError message={form.errors.end_date} />
                            </div>
                        </div>
                        {recurring && (
                            <p className="-mt-2 text-xs text-neutral-500">
                                {t(
                                    'unavailabilities.field_end_date_recurring_hint',
                                )}
                            </p>
                        )}

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="unavailability_start_time">
                                    {t('unavailabilities.field_start_time')}
                                </Label>
                                <Input
                                    id="unavailability_start_time"
                                    type="time"
                                    step={900}
                                    min="08:00"
                                    max="22:00"
                                    value={form.data.start_time}
                                    onChange={(e) =>
                                        form.setData(
                                            'start_time',
                                            e.target.value,
                                        )
                                    }
                                    required={recurring}
                                />
                                <InputError message={form.errors.start_time} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="unavailability_end_time">
                                    {t('unavailabilities.field_end_time')}
                                </Label>
                                <Input
                                    id="unavailability_end_time"
                                    type="time"
                                    step={900}
                                    min="08:00"
                                    max="22:00"
                                    value={form.data.end_time}
                                    onChange={(e) =>
                                        form.setData('end_time', e.target.value)
                                    }
                                    required={recurring}
                                />
                                <InputError message={form.errors.end_time} />
                            </div>
                        </div>
                        {!recurring && (
                            <p className="-mt-2 text-xs text-neutral-500">
                                {t('unavailabilities.field_times_ad_hoc_hint')}
                            </p>
                        )}

                        <div className="grid gap-2">
                            <Label htmlFor="unavailability_reason">
                                {t('unavailabilities.field_reason')}
                            </Label>
                            <textarea
                                id="unavailability_reason"
                                rows={3}
                                maxLength={500}
                                value={form.data.reason}
                                onChange={(e) =>
                                    form.setData('reason', e.target.value)
                                }
                                className={FIELD_CLASS}
                                required
                            />
                            <p className="text-xs text-neutral-500">
                                {t('unavailabilities.field_reason_hint')}
                            </p>
                            <InputError message={form.errors.reason} />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => handleOpenChange(false)}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            {t(
                                unavailability
                                    ? 'unavailabilities.submit_edit'
                                    : 'unavailabilities.submit_create',
                            )}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
