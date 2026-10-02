import { useHttp } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { check } from '@/routes/exams';
import type { SlotConflict } from '@/pages/timetable/components/schedule/types';
import type { ExamCheckResponse } from './types';

interface ExamSlot {
    exam_period_id: number;
    module_id: string;
    student_group_ids: number[];
    starts_at: string;
    ends_at: string;
}

type CheckForm = ExamSlot & { ignore_exam_id: number | null };

const EMPTY_CHECK: CheckForm = {
    exam_period_id: 0,
    module_id: '',
    student_group_ids: [],
    starts_at: '',
    ends_at: '',
    ignore_exam_id: null,
};

/**
 * The clashes the exam would cause once scheduled, re-checked whenever its module, groups or
 * times change. Nothing is saved; a draft may be kept despite them.
 */
export function useExamCheck(
    slot: ExamSlot | null,
    ignoreExamId: number | null,
) {
    const http = useHttp<CheckForm, ExamCheckResponse>(EMPTY_CHECK);
    const [conflicts, setConflicts] = useState<SlotConflict[]>([]);
    const key = slot === null ? null : JSON.stringify(slot);

    useEffect(() => {
        if (key === null) {
            setConflicts([]);

            return;
        }

        http.transform(() => ({
            ...JSON.parse(key),
            ignore_exam_id: ignoreExamId,
        }));
        http.post(check.url(), {
            onSuccess: (response) => setConflicts(response.hard_conflicts),
            // An incomplete or invalid form is reported when it is saved.
            onError: () => setConflicts([]),
        }).catch(() => undefined);

        return () => http.cancel();
        // The slot is compared by value through its serialized key.
    }, [key, ignoreExamId]);

    return conflicts;
}
