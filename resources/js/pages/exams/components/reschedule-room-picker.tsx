import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/i18n/LanguageContext';
import { FIELD_CLASS } from '@/lib/form-classes';
import { AllocationRoomList } from './allocation-room-list';
import { useExamAllocation } from './use-exam-allocation';

interface RescheduleRoomPickerProps {
    examId: number;
    roomIds: number[];
    onChange: (roomIds: number[]) => void;
}

/** New rooms for a rescheduled exam, in order; the server checks they are free at the new time. */
export function RescheduleRoomPicker({
    examId,
    roomIds,
    onChange,
}: RescheduleRoomPickerProps) {
    const { t } = useTranslation();
    const { allocation } = useExamAllocation(examId);

    if (allocation === null) {
        return <Spinner />;
    }

    const byId = new Map(allocation.rooms.map((room) => [room.id, room]));
    const chosen = roomIds.flatMap((id) => byId.get(id) ?? []);

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
                rooms={chosen}
                locked={false}
                onMove={move}
                onRemove={(id) =>
                    onChange(roomIds.filter((roomId) => roomId !== id))
                }
            />
            <select
                aria-label={t('exams.add_room')}
                value=""
                onChange={(e) => onChange([...roomIds, Number(e.target.value)])}
                className={FIELD_CLASS}
            >
                <option value="">{t('exams.add_room')}</option>
                {allocation.rooms
                    .filter((room) => !roomIds.includes(room.id))
                    .map((room) => (
                        <option key={room.id} value={room.id}>
                            {room.name} · {room.building} ·{' '}
                            {t('exams.room_capacity', {
                                capacity: room.exam_capacity,
                            })}
                        </option>
                    ))}
            </select>
        </div>
    );
}
