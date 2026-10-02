import { useTranslation } from '@/i18n/LanguageContext';
import { FIELD_CLASS } from '@/lib/form-classes';
import { AllocationRoomList } from './allocation-room-list';
import type { ExamAllocation } from './types';

interface OrderedRoomPickerProps {
    /** Every room that may be picked; busy ones are flagged, not hidden. */
    rooms: ExamAllocation['rooms'];
    /** The chosen rooms, in the order students are seated. */
    roomIds: number[];
    locked: boolean;
    /** Whether the busy flags apply: they describe the exam's current time. */
    flagBusy?: boolean;
    onChange: (roomIds: number[]) => void;
}

/** Picks an exam's rooms in order: add, move up or down, remove. */
export function OrderedRoomPicker({
    rooms,
    roomIds,
    locked,
    flagBusy = true,
    onChange,
}: OrderedRoomPickerProps) {
    const { t } = useTranslation();
    const byId = new Map(rooms.map((room) => [room.id, room]));

    const move = (index: number, offset: number) => {
        const next = [...roomIds];
        [next[index], next[index + offset]] = [
            next[index + offset],
            next[index],
        ];
        onChange(next);
    };

    return (
        <div className="grid gap-2">
            <AllocationRoomList
                rooms={roomIds.flatMap((id) => byId.get(id) ?? [])}
                locked={locked}
                onMove={move}
                onRemove={(id) =>
                    onChange(roomIds.filter((roomId) => roomId !== id))
                }
            />
            {locked ? null : (
                <select
                    aria-label={t('exams.add_room')}
                    value=""
                    onChange={(e) =>
                        onChange([...roomIds, Number(e.target.value)])
                    }
                    className={FIELD_CLASS}
                >
                    <option value="">{t('exams.add_room')}</option>
                    {rooms
                        .filter((room) => !roomIds.includes(room.id))
                        .map((room) => (
                            <option key={room.id} value={room.id}>
                                {room.name} · {room.building} ·{' '}
                                {t('exams.room_capacity', {
                                    capacity: room.exam_capacity,
                                })}
                                {flagBusy && room.busy
                                    ? ` · ${t('exams.room_busy')}`
                                    : ''}
                            </option>
                        ))}
                </select>
            )}
        </div>
    );
}
