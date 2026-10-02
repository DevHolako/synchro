import { router, useHttp } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from '@/i18n/LanguageContext';
import {
    check as checkBatch,
    store as storeBatch,
} from '@/routes/course-sessions/batch';
import { summarizeCheck } from './conflict-description';
import {
    buildSlots,
    generateDates,
    isoWeekday,
    rangesAreValid,
    slotMinutes,
} from './schedule-recurrence';
import type { RecurrenceRule, TimeRange } from './schedule-recurrence';
import type {
    Assignment,
    BatchCheckResponse,
    BatchPayload,
    BatchSlot,
    SchedulingOptions,
    SchedulingPrefill,
} from './types';
import type { TimetableLimits } from '../types';

export const WIZARD_STEPS = ['assignment', 'dates', 'review'] as const;

const DEFAULT_RANGES: TimeRange[] = [{ id: 1, start: '08:30', end: '12:30' }];
const EMPTY_PAYLOAD: BatchPayload = {
    module_id: null,
    teacher_id: null,
    room_id: null,
    student_group_ids: [],
    slots: [],
};

function initialRule(date: string): RecurrenceRule {
    return {
        weekdays: [isoWeekday(date)],
        startDate: date,
        endMode: 'count',
        count: 1,
        until: '',
    };
}

const slotsKey = (slots: BatchSlot[]) => JSON.stringify(slots);

interface UseScheduleWizardArgs {
    options: SchedulingOptions | null | undefined;
    limits: TimetableLimits;
    prefill: SchedulingPrefill;
    canOverride: boolean;
    onScheduled: (firstDate: string) => void;
}

/**
 * The wizard's state: what is being scheduled, when, and the conflict check of the result.
 *
 * The check is only trusted for the exact slots it ran on; any change to the slots
 * invalidates it until it runs again.
 */
export function useScheduleWizard({
    options,
    limits,
    prefill,
    canOverride,
    onScheduled,
}: UseScheduleWizardArgs) {
    const { t } = useTranslation();
    const [step, setStep] = useState(0);
    const [assignment, setAssignment] = useState<Assignment>(() => ({
        moduleId: prefill.moduleId,
        groupIds: prefill.groupId === null ? [] : [prefill.groupId],
        teacherId: prefill.teacherId,
        roomId: prefill.roomId,
    }));
    const [rule, setRule] = useState(() => initialRule(prefill.date));
    const [dates, setDates] = useState(() =>
        generateDates(initialRule(prefill.date), limits.batch_max_slots),
    );
    const [ranges, setRanges] = useState(DEFAULT_RANGES);
    const [removed, setRemoved] = useState<string[]>([]);
    const [justification, setJustification] = useState('');
    const [saveErrors, setSaveErrors] = useState<string[]>([]);
    const [checkedKey, setCheckedKey] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const check = useHttp<BatchPayload, BatchCheckResponse>(EMPTY_PAYLOAD);

    const module = options?.modules.find(
        (item) => item.id === assignment.moduleId,
    );
    const teacherId = assignment.teacherId ?? module?.teacher_id ?? null;
    const groupIds = assignment.groupIds.filter(
        (id) =>
            options?.groups.find((group) => group.id === id)?.program_id ===
            module?.program_id,
    );
    const roomId = options?.rooms.some((room) => room.id === assignment.roomId)
        ? assignment.roomId
        : null;
    const rangesValid = rangesAreValid(ranges);
    const slots = buildSlots(dates, ranges).filter(
        (slot) => !removed.includes(slot.starts_at),
    );
    const totalMinutes = slots.reduce(
        (sum, slot) => sum + slotMinutes(slot),
        0,
    );
    const payload: BatchPayload = {
        module_id: module?.id ?? null,
        teacher_id: teacherId,
        room_id: roomId,
        student_group_ids: groupIds,
        slots,
    };

    const response = checkedKey === slotsKey(slots) ? check.response : null;
    const summary = response ? summarizeCheck(response.slots) : null;
    const checkErrors = [...new Set(Object.values(check.errors))].filter(
        (error): error is string => typeof error === 'string',
    );

    const canContinue =
        step === 0
            ? module !== undefined &&
              groupIds.length > 0 &&
              teacherId !== null &&
              roomId !== null
            : rangesValid &&
              slots.length > 0 &&
              slots.length <= limits.batch_max_slots;
    const canSubmit =
        summary !== null &&
        slots.length > 0 &&
        !summary.blocked &&
        !submitting &&
        !check.processing &&
        (!summary.needsJustification ||
            (canOverride &&
                justification.trim().length >= limits.justification_min));

    const runCheck = (nextSlots: BatchSlot[]) => {
        const key = slotsKey(nextSlots);

        setCheckedKey(null);
        check.transform(() => ({ ...payload, slots: nextSlots }));
        check
            .post(checkBatch.url())
            .then(() => setCheckedKey(key))
            .catch(() => setCheckedKey(null));
    };

    const updateRule = (patch: Partial<RecurrenceRule>) => {
        const next = { ...rule, ...patch };

        setRule(next);
        setDates(generateDates(next, limits.batch_max_slots));
    };

    const next = () => {
        if (step === 1) {
            setSaveErrors([]);
            runCheck(slots);
        }

        setStep(step + 1);
    };

    const back = () => {
        if (step === 2) {
            setRemoved([]);
            setSaveErrors([]);
        }

        setStep(step - 1);
    };

    const removeSlot = (startsAt: string) => {
        setRemoved((current) => [...current, startsAt]);
        runCheck(slots.filter((slot) => slot.starts_at !== startsAt));
    };

    const submit = () => {
        router.post(
            storeBatch.url(),
            {
                ...payload,
                ...(summary?.needsJustification
                    ? { force_override: true, justification }
                    : {}),
            },
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => setSubmitting(true),
                onFinish: () => setSubmitting(false),
                onSuccess: () => onScheduled(slots[0].starts_at.slice(0, 10)),
                onError: (errors) => {
                    setSaveErrors([
                        t('schedule.save_failed'),
                        ...Object.values(errors),
                    ]);
                    runCheck(slots);
                },
            },
        );
    };

    return {
        step,
        assignment,
        teacherId,
        groupIds,
        rule,
        dates,
        ranges,
        rangesValid,
        slots,
        totalMinutes,
        response,
        checking: check.processing,
        errors: [...saveErrors, ...checkErrors],
        justification,
        submitting,
        canContinue,
        canSubmit,
        next,
        back,
        submit,
        removeSlot,
        updateRule,
        setJustification,
        updateAssignment: (patch: Partial<Assignment>) =>
            setAssignment((current) => ({ ...current, ...patch })),
        removeDate: (date: string) =>
            setDates((current) => current.filter((item) => item !== date)),
        addDate: (date: string) =>
            setDates((current) =>
                current.includes(date)
                    ? current
                    : [...current, date].toSorted(),
            ),
        updateRange: (id: number, patch: Partial<TimeRange>) =>
            setRanges((current) =>
                current.map((range) =>
                    range.id === id ? { ...range, ...patch } : range,
                ),
            ),
        addRange: () =>
            setRanges((current) => [
                ...current,
                {
                    id: Math.max(...current.map((range) => range.id)) + 1,
                    start: '',
                    end: '',
                },
            ]),
        removeRange: (id: number) =>
            setRanges((current) => current.filter((range) => range.id !== id)),
    };
}
