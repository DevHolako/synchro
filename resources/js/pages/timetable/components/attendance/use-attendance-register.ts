import { useHttp } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';
import { useTranslation } from '@/i18n/LanguageContext';
import {
    show as showAttendance,
    update as updateAttendance,
} from '@/routes/course-sessions/attendance';
import type {
    AttendanceMark,
    AttendanceStatus,
    DraftMarks,
    RosterStudent,
} from './types';

type RegisterResponse = { students: RosterStudent[] };

function draftFrom(students: RosterStudent[]): DraftMarks {
    return Object.fromEntries(
        students.map((student) => [
            student.student_id,
            { status: student.status, remarks: student.remarks ?? '' },
        ]),
    );
}

/**
 * Loads a session's register, keeps the marks being edited, and saves them in one request.
 */
export function useAttendanceRegister(sessionId: number, onSaved: () => void) {
    const { t } = useTranslation();
    const loader = useHttp<Record<string, never>, RegisterResponse>({});
    const saver = useHttp<{ marks: AttendanceMark[] }>({
        marks: [],
    });
    const [students, setStudents] = useState<RosterStudent[] | null>(null);
    const [draft, setDraft] = useState<DraftMarks>({});

    useEffect(() => {
        loader
            .get(showAttendance.url(sessionId), {
                onSuccess: (response) => {
                    setStudents(response.students);
                    setDraft(draftFrom(response.students));
                },
                onHttpException: () => {
                    toast.error(t('attendance.load_failed'));
                },
            })
            .catch(() => undefined);
        // The sheet is mounted per session: load once.
    }, [sessionId]);

    const setStatus = useCallback(
        (studentId: number, status: AttendanceStatus | null) =>
            setDraft((current) => ({
                ...current,
                [studentId]: { ...current[studentId], status },
            })),
        [],
    );

    const setRemarks = useCallback(
        (studentId: number, remarks: string) =>
            setDraft((current) => ({
                ...current,
                [studentId]: { ...current[studentId], remarks },
            })),
        [],
    );

    const markAllPresent = () =>
        setDraft((current) =>
            Object.fromEntries(
                Object.entries(current).map(([id, mark]) => [
                    id,
                    { ...mark, status: 'present' as const },
                ]),
            ),
        );

    const save = () => {
        // Every row is sent: a cleared status removes a mark saved earlier.
        const marks = Object.entries(draft).map(([id, mark]) => ({
            student_id: Number(id),
            status: mark.status,
            remarks: mark.remarks.trim() || null,
        }));

        saver.transform(() => ({ marks }));
        saver
            .put(updateAttendance.url(sessionId), {
                onSuccess: () => {
                    toast.success(t('attendance.saved'));
                    onSaved();
                },
                onError: (errors) =>
                    toast.error(
                        Object.values(errors)[0] ?? t('attendance.save_failed'),
                    ),
                onHttpException: () => {
                    toast.error(t('attendance.save_failed'));
                },
            })
            .catch(() => undefined);
    };

    return {
        students,
        draft,
        saving: saver.processing,
        setStatus,
        setRemarks,
        markAllPresent,
        save,
    };
}
