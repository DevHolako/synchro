import { useMemo } from 'react';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/i18n/LanguageContext';
import { summarizeCheck } from './conflict-description';
import { ScheduleSlotRow } from './schedule-slot-row';
import { ScheduleSyllabusMeter } from './schedule-syllabus-meter';
import type { BatchCheckResponse, BatchSlot } from './types';

const FIELD_CLASS =
    'w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-800 dark:bg-neutral-900';

interface ScheduleStepReviewProps {
    response: BatchCheckResponse | null;
    checking: boolean;
    errors: string[];
    canOverride: boolean;
    justification: string;
    onJustificationChange: (value: string) => void;
    onRemoveSlot: (startsAt: string) => void;
}

const NO_OVERLAPS: BatchSlot[] = [];

export function ScheduleStepReview({
    response,
    checking,
    errors,
    canOverride,
    justification,
    onJustificationChange,
    onRemoveSlot,
}: ScheduleStepReviewProps) {
    const { t } = useTranslation();

    const overlapping = useMemo(
        () =>
            response?.slots.map((slot) =>
                slot.overlaps.length === 0
                    ? NO_OVERLAPS
                    : slot.overlaps.map((index) => response.slots[index]),
            ) ?? [],
        [response],
    );

    const summary = response ? summarizeCheck(response.slots) : null;

    return (
        <div className="grid gap-4">
            {errors.length > 0 ? (
                <div className="grid gap-1 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
                    {errors.map((error) => (
                        <p key={error}>{error}</p>
                    ))}
                </div>
            ) : null}

            {checking ? (
                <p className="flex items-center gap-2 text-sm text-neutral-500">
                    <Spinner />
                    {t('schedule.checking')}
                </p>
            ) : null}

            {response ? (
                <>
                    <ScheduleSyllabusMeter syllabus={response.syllabus} />

                    <ul className="max-h-72 divide-y divide-neutral-100 overflow-y-auto dark:divide-neutral-800">
                        {response.slots.map((slot) => (
                            <ScheduleSlotRow
                                key={slot.starts_at}
                                slot={slot}
                                overlapping={
                                    overlapping[slot.index] ?? NO_OVERLAPS
                                }
                                onRemove={onRemoveSlot}
                            />
                        ))}
                    </ul>

                    {summary?.blocked ? (
                        <p className="text-sm text-red-700 dark:text-red-400">
                            {t('schedule.hard_block')}
                        </p>
                    ) : null}

                    {!summary?.blocked &&
                    summary?.needsJustification &&
                    !canOverride ? (
                        <p className="text-sm text-amber-700 dark:text-amber-400">
                            {t('schedule.soft_no_permission')}
                        </p>
                    ) : null}

                    {!summary?.blocked &&
                    summary?.needsJustification &&
                    canOverride ? (
                        <div className="grid gap-2">
                            <p className="text-sm text-amber-700 dark:text-amber-400">
                                {t('schedule.soft_notice')}
                            </p>
                            <Label htmlFor="schedule_justification">
                                {t('schedule.justification_label')}
                            </Label>
                            <textarea
                                id="schedule_justification"
                                rows={3}
                                maxLength={1000}
                                value={justification}
                                placeholder={t(
                                    'schedule.justification_placeholder',
                                )}
                                onChange={(e) =>
                                    onJustificationChange(e.target.value)
                                }
                                className={FIELD_CLASS}
                            />
                            <p className="text-xs text-neutral-500">
                                {t('schedule.justification_hint')}
                            </p>
                        </div>
                    ) : null}
                </>
            ) : null}
        </div>
    );
}
