import { Spinner } from '@/components/ui/spinner';
import { OrderedRoomPicker } from './ordered-room-picker';
import { useExamAllocation } from './use-exam-allocation';

interface RescheduleRoomPickerProps {
    examId: number;
    roomIds: number[];
    onChange: (roomIds: number[]) => void;
}

/**
 * New rooms for a rescheduled exam, in order. Busy flags are left out (they describe the current
 * time); the server checks the rooms are free at the new one.
 */
export function RescheduleRoomPicker({
    examId,
    roomIds,
    onChange,
}: RescheduleRoomPickerProps) {
    const { allocation } = useExamAllocation(examId);

    if (allocation === null) {
        return <Spinner />;
    }

    return (
        <OrderedRoomPicker
            rooms={allocation.rooms}
            roomIds={roomIds}
            locked={false}
            flagBusy={false}
            onChange={onChange}
        />
    );
}
