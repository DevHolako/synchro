import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/i18n/LanguageContext';
import { FIELD_CLASS } from '@/lib/form-classes';
import { AllocationRoomList } from './allocation-room-list';
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
    const locked =
        allocation.state !== 'draft' && allocation.state !== 'scheduled';
    const byId = new Map(allocation.rooms.map((room) => [room.id, room]));
    const chosen = roomIds.flatMap((id) => byId.get(id) ?? []);
    const seats = chosen.reduce((sum, room) => sum + room.exam_capacity, 0);

    const move = (index: number, offset: number) =>
        setRoomIds((current) => {
            const next = [...current];
            [next[index], next[index + offset]] = [
                next[index + offset],
                next[index],
            ];

            return next;
        });

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

            <AllocationRoomList
                rooms={chosen}
                locked={locked}
                onMove={move}
                onRemove={(id) =>
                    setRoomIds((current) =>
                        current.filter((roomId) => roomId !== id),
                    )
                }
            />

            {locked ? (
                <p className="text-xs text-neutral-500">
                    {t('exams.rooms_locked')}
                </p>
            ) : (
                <>
                    <select
                        aria-label={t('exams.add_room')}
                        value=""
                        onChange={(e) =>
                            setRoomIds((current) => [
                                ...current,
                                Number(e.target.value),
                            ])
                        }
                        className={FIELD_CLASS}
                    >
                        <option value="">{t('exams.add_room')}</option>
                        {allocation.rooms
                            .filter((room) => !roomIds.includes(room.id))
                            .map((room) => (
                                <option
                                    key={room.id}
                                    value={room.id}
                                    disabled={room.busy}
                                >
                                    {room.name} · {room.building} ·{' '}
                                    {t(
                                        room.busy
                                            ? 'exams.room_busy'
                                            : 'exams.room_capacity',
                                        { capacity: room.exam_capacity },
                                    )}
                                </option>
                            ))}
                    </select>

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
