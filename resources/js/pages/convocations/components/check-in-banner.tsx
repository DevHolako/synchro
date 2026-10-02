import { BadgeCheck, CircleAlert, Clock, UserCheck } from 'lucide-react';
import { useTranslation } from '@/i18n/LanguageContext';
import { timeOf } from '@/pages/timetable/components/wall-clock-format';
import type { CheckInCandidate, CheckInState } from './types';

const TONES = {
    danger: 'border-rose-300 bg-rose-50 text-rose-800 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-200',
    info: 'border-sky-300 bg-sky-50 text-sky-800 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200',
    warning:
        'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200',
    success:
        'border-emerald-300 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
};

/** The one thing the invigilator must see first: wrong room, already present, closed, or valid. */
export function CheckInBanner({
    candidate,
    checkIn,
}: {
    candidate: CheckInCandidate;
    checkIn: CheckInState;
}) {
    const { t } = useTranslation();

    const [tone, Icon, message] = checkIn.wrong_room
        ? ([
              'danger',
              CircleAlert,
              t('check_in.wrong_room', { room: candidate.room }),
          ] as const)
        : checkIn.checked_in_at
          ? ([
                'info',
                UserCheck,
                t('check_in.already_present', {
                    time: checkIn.checked_in_at,
                    by: checkIn.checked_in_by ?? '',
                }),
            ] as const)
          : !checkIn.open
            ? ([
                  'warning',
                  Clock,
                  t('check_in.closed', { time: timeOf(checkIn.opens_at) }),
              ] as const)
            : (['success', BadgeCheck, t('convocations.valid')] as const);

    return (
        <div
            className={`flex items-center gap-2 rounded-lg border p-3 ${TONES[tone]}`}
        >
            <Icon className="size-5 shrink-0" />
            <span className="font-semibold">{message}</span>
        </div>
    );
}
