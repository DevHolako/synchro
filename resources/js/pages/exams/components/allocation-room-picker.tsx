import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/i18n/LanguageContext';
import { FIELD_CLASS } from '@/lib/form-classes';
import { OrderedRoomPicker } from './ordered-room-picker';
import type { ExamAllocation } from './types';

interface AllocationRoomPickerProps {
    allocation: ExamAllocation;
    /** Whether the override permission lets this user force a single room. */
    canForce: boolean;
    errors: Record<string, string | undefined>;
    saving: boolean;
    onSave: (roomIds: number[], justification: string | null) => void;
}

/** Mounted once the allocation has loaded, so it starts from the saved rooms. */
export function AllocationRoomPicker({
    allocation,
    canForce,
    errors,
    saving,
    onSave,
}: AllocationRoomPickerProps) {
    const { t } = useTranslation();
    const [roomIds, setRoomIds] = useState(() =>
        allocation.assignments.map((assignment) => assignment.room_id),
    );
    const [force, setForce] = useState(allocation.force_single_room);
    const [justification, setJustification] = useState('');
    const locked = !allocation.rooms_editable;
    const capacityOf = new Map(
        allocation.rooms.map((room) => [room.id, room.exam_capacity]),
    );
    const seats = roomIds.reduce(
        (sum, id) => sum + (capacityOf.get(id) ?? 0),
        0,
    );

    return (
        <section className="grid gap-3">
            <div className="flex items-baseline justify-between gap-2">
                <h3 className="text-sm font-semibold">
                    {t('exams.rooms_title')}
                </h3>
                <span className="text-xs text-neutral-500">
                    {t('exams.rooms_seats', {
                        seats,
                        students: allocation.students_count,
                    })}
                </span>
            </div>

            <OrderedRoomPicker
                rooms={allocation.rooms}
                roomIds={roomIds}
                locked={locked}
                onChange={setRoomIds}
            />

            {locked ? (
                <p className="text-xs text-neutral-500">
                    {t('exams.rooms_locked')}
                </p>
            ) : (
                <>
                    {canForce ? (
                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox
                                checked={force}
                                onCheckedChange={(checked) =>
                                    setForce(checked === true)
                                }
                            />
                            {t('exams.force_single_room')}
                        </label>
                    ) : null}
                    {force ? (
                        <textarea
                            aria-label={t('exams.force_justification')}
                            placeholder={t('exams.force_justification')}
                            rows={2}
                            maxLength={1000}
                            value={justification}
                            onChange={(e) => setJustification(e.target.value)}
                            className={FIELD_CLASS}
                        />
                    ) : null}

                    <InputError
                        message={
                            errors.room_ids ??
                            errors.justification ??
                            errors.exam ??
                            errors.conflicts
                        }
                    />

                    <Button
                        type="button"
                        disabled={saving || roomIds.length === 0}
                        onClick={() =>
                            onSave(roomIds, force ? justification : null)
                        }
                    >
                        {saving && <Spinner />}
                        {t('exams.save_rooms')}
                    </Button>
                </>
            )}
        </section>
    );
}
