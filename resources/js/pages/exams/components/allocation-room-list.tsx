import { ArrowDown, ArrowUp, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';
import type { ExamAllocation } from './types';

interface AllocationRoomListProps {
    /** The chosen rooms, in the order students are seated. */
    rooms: ExamAllocation['rooms'];
    locked: boolean;
    onMove: (index: number, offset: number) => void;
    onRemove: (roomId: number) => void;
}

export function AllocationRoomList({
    rooms,
    locked,
    onMove,
    onRemove,
}: AllocationRoomListProps) {
    const { t } = useTranslation();

    return (
        <ol className="grid gap-1">
            {rooms.map((room, index) => (
                <li
                    key={room.id}
                    className="flex items-center gap-2 rounded-md border border-neutral-200 px-3 py-1.5 text-sm dark:border-neutral-800"
                >
                    <span className="w-5 text-xs text-neutral-500">
                        {index + 1}.
                    </span>
                    <span className="flex-1">
                        {room.name} · {room.building}
                    </span>
                    <span className="text-xs text-neutral-500">
                        {t('exams.room_capacity', {
                            capacity: room.exam_capacity,
                        })}
                    </span>
                    {locked ? null : (
                        <>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                aria-label={t('exams.move_up')}
                                disabled={index === 0}
                                onClick={() => onMove(index, -1)}
                            >
                                <ArrowUp className="size-4" />
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                aria-label={t('exams.move_down')}
                                disabled={index === rooms.length - 1}
                                onClick={() => onMove(index, 1)}
                            >
                                <ArrowDown className="size-4" />
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                aria-label={t('exams.remove_room')}
                                onClick={() => onRemove(room.id)}
                            >
                                <X className="size-4" />
                            </Button>
                        </>
                    )}
                </li>
            ))}
        </ol>
    );
}
