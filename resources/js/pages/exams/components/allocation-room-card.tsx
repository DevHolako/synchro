import { TriangleAlert, X } from 'lucide-react';
import { memo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/i18n/LanguageContext';
import { FIELD_CLASS } from '@/lib/form-classes';
import type { SlotConflict } from '@/pages/timetable/components/schedule/types';
import { AllocationStaffConflicts } from './allocation-staff-conflicts';
import type { AllocatedRoom } from './types';

interface AllocationRoomCardProps {
    room: AllocatedRoom;
    teachers: { id: number; name: string }[];
    assistantThreshold: number;
    /** False once the exam has started. */
    editable: boolean;
    saving: boolean;
    /** Unavailabilities to justify before saving this room's staff again. */
    conflicts: SlotConflict[] | null;
    onSave: (
        assignmentId: number,
        leadId: number,
        assistantIds: number[],
        justification?: string,
    ) => void;
}

/** One exam room: its alphabetical range and its invigilators. */
export const AllocationRoomCard = memo(function AllocationRoomCard({
    room,
    teachers,
    assistantThreshold,
    editable,
    saving,
    conflicts,
    onSave,
}: AllocationRoomCardProps) {
    const { t } = useTranslation();
    const [leadId, setLeadId] = useState(
        () =>
            room.invigilators.find(
                (invigilator) => invigilator.role === 'principal',
            )?.teacher_id ?? 0,
    );
    const [assistantIds, setAssistantIds] = useState(() =>
        room.invigilators
            .filter((invigilator) => invigilator.role === 'adjoint')
            .map((invigilator) => invigilator.teacher_id),
    );
    const [justification, setJustification] = useState('');
    const nameOf = (id: number) =>
        teachers.find((teacher) => teacher.id === id)?.name ?? '';
    const needsAssistant =
        room.allocated_students_count > assistantThreshold &&
        assistantIds.length === 0;

    return (
        <li className="grid gap-2 rounded-lg border border-neutral-200 p-3 dark:border-neutral-800">
            <div className="flex flex-wrap items-baseline justify-between gap-2">
                <span className="font-semibold">
                    {room.room} · {room.building}
                </span>
                <span className="text-xs text-neutral-500">
                    {t('exams.room_students', {
                        count: room.allocated_students_count,
                        capacity: room.exam_capacity,
                    })}
                </span>
            </div>
            {room.first_surname ? (
                <p className="text-xs text-neutral-500">
                    {t('exams.room_range', {
                        from: room.first_surname,
                        to: room.last_surname ?? '',
                    })}
                </p>
            ) : null}

            <select
                aria-label={t('exams.lead_invigilator')}
                value={leadId}
                disabled={!editable}
                onChange={(e) => setLeadId(Number(e.target.value))}
                className={FIELD_CLASS}
            >
                <option value={0}>{t('exams.pick_lead')}</option>
                {teachers.map((teacher) => (
                    <option key={teacher.id} value={teacher.id}>
                        {teacher.name}
                    </option>
                ))}
            </select>

            <div className="flex flex-wrap gap-1">
                {assistantIds.map((id) => (
                    <span
                        key={id}
                        className="inline-flex items-center gap-1 rounded-md bg-neutral-100 px-2 py-0.5 text-xs dark:bg-neutral-800"
                    >
                        {nameOf(id)}
                        {editable ? (
                            <button
                                type="button"
                                aria-label={t('exams.remove_assistant')}
                                onClick={() =>
                                    setAssistantIds((current) =>
                                        current.filter(
                                            (assistant) => assistant !== id,
                                        ),
                                    )
                                }
                            >
                                <X className="size-3" />
                            </button>
                        ) : null}
                    </span>
                ))}
            </div>
            {editable ? (
                <select
                    aria-label={t('exams.add_assistant')}
                    value=""
                    onChange={(e) =>
                        setAssistantIds((current) => [
                            ...current,
                            Number(e.target.value),
                        ])
                    }
                    className={FIELD_CLASS}
                >
                    <option value="">{t('exams.add_assistant')}</option>
                    {teachers
                        .filter(
                            (teacher) =>
                                teacher.id !== leadId &&
                                !assistantIds.includes(teacher.id),
                        )
                        .map((teacher) => (
                            <option key={teacher.id} value={teacher.id}>
                                {teacher.name}
                            </option>
                        ))}
                </select>
            ) : null}

            {needsAssistant ? (
                <p className="flex items-center gap-1.5 text-xs text-amber-700 dark:text-amber-300">
                    <TriangleAlert className="size-3.5" />
                    {t('exams.assistant_recommended', {
                        threshold: assistantThreshold,
                    })}
                </p>
            ) : null}

            {conflicts ? (
                <AllocationStaffConflicts
                    conflicts={conflicts}
                    justification={justification}
                    onJustificationChange={setJustification}
                />
            ) : null}

            {editable ? (
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    disabled={saving || leadId === 0}
                    onClick={() =>
                        onSave(
                            room.id,
                            leadId,
                            assistantIds,
                            conflicts ? justification : undefined,
                        )
                    }
                >
                    {saving && <Spinner />}
                    {t(
                        conflicts
                            ? 'exams.save_staff_anyway'
                            : 'exams.save_staff',
                    )}
                </Button>
            ) : null}
        </li>
    );
});
