import { router, useHttp } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { useTranslation } from '@/i18n/LanguageContext';
import type { SlotConflict } from '@/pages/timetable/components/schedule/types';
import { show as showAllocation } from '@/routes/exams/allocation';
import { update as updateInvigilators } from '@/routes/exams/invigilators';
import { update as updateRooms } from '@/routes/exams/rooms';
import type { ExamAllocation } from './types';

const SOFT_CONFLICT_STATUS = 409;

interface RoomsForm {
    room_ids: number[];
    force_single_room: boolean;
    justification: string;
}

interface StaffForm {
    lead_id: number | null;
    assistant_ids: number[];
    force_override: boolean;
    justification: string;
}

/** Unavailabilities found when staffing a room: saving again needs a justification. */
export interface PendingStaffing {
    assignmentId: number;
    conflicts: SlotConflict[];
}

/**
 * Loads an exam's rooms and invigilators, and saves either; each save answers with the new
 * allocation, and the exams list reloads behind the sheet.
 */
export function useExamAllocation(examId: number) {
    const { t } = useTranslation();
    const loader = useHttp<Record<string, never>, ExamAllocation>({});
    const roomsHttp = useHttp<RoomsForm, ExamAllocation>({
        room_ids: [],
        force_single_room: false,
        justification: '',
    });
    const staffHttp = useHttp<StaffForm, ExamAllocation>({
        lead_id: null,
        assistant_ids: [],
        force_override: false,
        justification: '',
    });
    const [allocation, setAllocation] = useState<ExamAllocation | null>(null);
    const [pending, setPending] = useState<PendingStaffing | null>(null);

    useEffect(() => {
        loader
            .get(showAllocation.url(examId), {
                onSuccess: setAllocation,
                onHttpException: () => {
                    toast.error(t('exams.allocation_failed'));
                },
            })
            .catch(() => undefined);
        // The sheet is mounted per exam: load once.
    }, [examId]);

    const saved = (next: ExamAllocation) => {
        setAllocation(next);
        toast.success(t('exams.allocation_saved'));
        router.reload({ only: ['exams', 'stats'] });
    };

    const saveRooms = (roomIds: number[], justification: string | null) => {
        roomsHttp.transform(() => ({
            room_ids: roomIds,
            force_single_room: justification !== null,
            justification: justification ?? '',
        }));
        roomsHttp
            .put(updateRooms.url(examId), {
                onSuccess: (next) => {
                    saved(next);

                    if (next.released && next.released.length > 0) {
                        toast.warning(
                            t('exams.invigilators_released', {
                                names: next.released.join(', '),
                            }),
                        );
                    }
                },
            })
            .catch(() => undefined);
    };

    const saveStaff = (
        assignmentId: number,
        leadId: number,
        assistantIds: number[],
        justification?: string,
    ) => {
        staffHttp.transform(() => ({
            lead_id: leadId,
            assistant_ids: assistantIds,
            force_override: justification !== undefined,
            justification: justification ?? '',
        }));
        staffHttp
            .put(
                updateInvigilators.url({
                    exam: examId,
                    assignment: assignmentId,
                }),
                {
                    onSuccess: (next) => {
                        setPending(null);
                        saved(next);
                    },
                    onHttpException: (response) => {
                        if (response.status === SOFT_CONFLICT_STATUS) {
                            const body = JSON.parse(response.data) as {
                                soft_conflicts: SlotConflict[];
                            };
                            setPending({
                                assignmentId,
                                conflicts: body.soft_conflicts,
                            });
                        } else {
                            toast.error(t('exams.allocation_failed'));
                        }
                    },
                },
            )
            .catch(() => undefined);
    };

    // One stable function for the memoized room cards; it always runs the latest saveStaff.
    const saveStaffRef = useRef(saveStaff);

    useEffect(() => {
        saveStaffRef.current = saveStaff;
    });
    const stableSaveStaff = useCallback(
        (...args: Parameters<typeof saveStaff>) =>
            saveStaffRef.current(...args),
        [],
    );

    return {
        allocation,
        rooms: {
            save: saveRooms,
            errors: roomsHttp.errors,
            saving: roomsHttp.processing,
        },
        staff: {
            save: stableSaveStaff,
            errors: staffHttp.errors,
            saving: staffHttp.processing,
            pending,
        },
    };
}
