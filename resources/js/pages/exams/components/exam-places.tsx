import { Link } from '@inertiajs/react';
import { useTranslation } from '@/i18n/LanguageContext';
import { checkIn as roomCheckIn } from '@/routes/exams/rooms';
import type { Exam } from './types';

/** Where the exam happens: every room for managers, otherwise the viewer's own seat or room. */
export function ExamPlaces({
    exam,
    canManage,
}: {
    exam: Exam;
    canManage: boolean;
}) {
    const { t } = useTranslation();

    if (exam.my_seat) {
        return (
            <div className="mt-1 text-xs font-medium text-neutral-900 dark:text-neutral-100">
                {t('exams.my_seat', {
                    room: exam.my_seat.room,
                    seat: exam.my_seat.seat,
                })}
            </div>
        );
    }

    if (exam.my_invigilation) {
        return (
            <div className="mt-1 grid gap-0.5 text-xs font-medium text-neutral-900 dark:text-neutral-100">
                {t(`exams.my_invigilation_${exam.my_invigilation.role}`, {
                    room: exam.my_invigilation.room,
                })}
                {exam.state === 'published' &&
                exam.my_invigilation.can_check_in ? (
                    <Link
                        href={
                            roomCheckIn({
                                exam: exam.id,
                                assignment: exam.my_invigilation.assignment_id,
                            }).url
                        }
                        className="text-sky-700 hover:underline dark:text-sky-300"
                    >
                        {t('check_in.open_room_list')}
                    </Link>
                ) : null}
            </div>
        );
    }

    if (!canManage || exam.rooms.length === 0) {
        return null;
    }

    return (
        <ul className="mt-1 text-xs text-neutral-500">
            {exam.rooms.map((room) => (
                <li key={room.id}>
                    {t('exams.room_line', {
                        room: room.name,
                        count: room.students_count,
                    })}
                </li>
            ))}
        </ul>
    );
}
