import type { SlotConflict } from './types';

type Translate = (
    key: string,
    params?: Record<string, string | number>,
) => string;

const timeOf = (value: string) => value.slice(11, 16);

/** A conflict in the UI language (the server's own messages follow APP_LOCALE). */
export function describeConflict(conflict: SlotConflict, t: Translate): string {
    return t(`schedule.conflict_${conflict.type}`, {
        name: conflict.resource_name,
        start: timeOf(conflict.starts_at),
        end: timeOf(conflict.ends_at),
        capacity: conflict.details.capacity ?? '',
        headcount: conflict.details.headcount ?? '',
        reason: conflict.details.reason ?? '',
    });
}

export interface CheckSummary {
    /** Some slot has a hard conflict or overlaps another slot of the batch. */
    blocked: boolean;
    /** Some slot breaks a soft rule and needs a justification. */
    needsJustification: boolean;
}

export function summarizeCheck(
    slots: {
        has_hard_conflicts: boolean;
        has_soft_conflicts: boolean;
        overlaps: number[];
    }[],
): CheckSummary {
    return {
        blocked: slots.some(
            (slot) => slot.has_hard_conflicts || slot.overlaps.length > 0,
        ),
        needsJustification: slots.some((slot) => slot.has_soft_conflicts),
    };
}
