import { usePage } from '@inertiajs/react';
import InputError from '@/components/input-error';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/i18n/LanguageContext';
import { Permission } from '@/lib/permissions';
import { useSchoolClock } from '@/pages/timetable/components/use-school-clock';
import {
    formatDay,
    timeOf,
} from '@/pages/timetable/components/wall-clock-format';
import { AllocationRoomCard } from './allocation-room-card';
import { AllocationRoomPicker } from './allocation-room-picker';
import type { Exam } from './types';
import { useExamAllocation } from './use-exam-allocation';

interface ExamAllocationSheetProps {
    exam: Exam;
    onClose: () => void;
}

/** An exam's rooms, alphabetical split and invigilators. */
export function ExamAllocationSheet({
    exam,
    onClose,
}: ExamAllocationSheetProps) {
    const { t, locale } = useTranslation();
    const { auth } = usePage().props;
    const { allocation, rooms, staff } = useExamAllocation(exam.id);
    const schoolNow = useSchoolClock();
    const canForce = auth.permissions.includes(
        Permission.OverrideSoftConflicts,
    );
    const staffErrors = staff.errors as Record<string, string | undefined>;
    // Invigilators may change until the exam starts, even once it is published.
    const staffable =
        exam.start > schoolNow() &&
        exam.state !== 'completed' &&
        exam.state !== 'archived';

    return (
        <Sheet open onOpenChange={(open) => !open && onClose()}>
            <SheetContent className="flex w-full flex-col gap-0 sm:max-w-lg">
                <SheetHeader>
                    <SheetTitle>{t('exams.allocation_title')}</SheetTitle>
                    <SheetDescription>
                        {exam.module.code} · {exam.module.name} ·{' '}
                        {formatDay(exam.start, locale, 'medium')}{' '}
                        {timeOf(exam.start)}–{timeOf(exam.end)}
                    </SheetDescription>
                </SheetHeader>

                <div className="grid flex-1 content-start gap-6 overflow-y-auto px-4 pb-6">
                    {allocation === null ? (
                        <p className="flex items-center gap-2 py-8 text-sm text-neutral-500">
                            <Spinner />
                            {t('exams.allocation_loading')}
                        </p>
                    ) : (
                        <>
                            <AllocationRoomPicker
                                allocation={allocation}
                                canForce={canForce}
                                errors={
                                    rooms.errors as Record<
                                        string,
                                        string | undefined
                                    >
                                }
                                saving={rooms.saving}
                                onSave={rooms.save}
                            />

                            {allocation.assignments.length > 0 ? (
                                <section className="grid gap-3">
                                    <h3 className="text-sm font-semibold">
                                        {t('exams.invigilators_title')}
                                    </h3>
                                    <InputError
                                        message={
                                            staffErrors.assistant_ids ??
                                            staffErrors.lead_id ??
                                            staffErrors.exam ??
                                            staffErrors.conflicts
                                        }
                                    />
                                    <ul className="grid gap-3">
                                        {allocation.assignments.map((room) => (
                                            <AllocationRoomCard
                                                key={`${room.id}-${room.invigilators.map((invigilator) => invigilator.teacher_id).join('.')}`}
                                                room={room}
                                                teachers={allocation.teachers}
                                                assistantThreshold={
                                                    allocation.assistant_threshold
                                                }
                                                editable={staffable}
                                                saving={staff.saving}
                                                conflicts={
                                                    staff.pending
                                                        ?.assignmentId ===
                                                    room.id
                                                        ? staff.pending
                                                              .conflicts
                                                        : null
                                                }
                                                onSave={staff.save}
                                            />
                                        ))}
                                    </ul>
                                </section>
                            ) : null}
                        </>
                    )}
                </div>
            </SheetContent>
        </Sheet>
    );
}
