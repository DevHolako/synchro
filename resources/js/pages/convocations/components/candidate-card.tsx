import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { useTranslation } from '@/i18n/LanguageContext';
import {
    formatDay,
    timeOf,
} from '@/pages/timetable/components/wall-clock-format';
import type { CheckInCandidate, CheckInExam } from './types';

/** Who the candidate is and where they sit (no photo yet: initials stand in). */
export function CandidateCard({
    candidate,
    exam,
}: {
    candidate: CheckInCandidate;
    exam: CheckInExam;
}) {
    const { t, locale } = useTranslation();
    const initials = useInitials();

    const facts = [
        [t('convocations.student_number'), candidate.student_number ?? '—'],
        [t('convocations.group'), candidate.group ?? '—'],
        [t('convocations.exam'), exam.module],
        [
            t('convocations.when'),
            `${formatDay(exam.start, locale, 'medium')} · ${timeOf(exam.start)}–${timeOf(exam.end)}`,
        ],
    ];

    return (
        <>
            <div className="rounded-lg border border-neutral-200 bg-white p-4 shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
                <div className="flex items-center gap-3">
                    <Avatar className="size-14">
                        <AvatarFallback className="text-lg font-semibold">
                            {initials(candidate.name)}
                        </AvatarFallback>
                    </Avatar>
                    <h1 className="text-xl font-bold">{candidate.name}</h1>
                </div>
                <dl className="mt-3 grid gap-2 text-sm">
                    {facts.map(([label, value]) => (
                        <div key={label} className="flex justify-between gap-3">
                            <dt className="text-neutral-500">{label}</dt>
                            <dd className="text-right font-medium">{value}</dd>
                        </div>
                    ))}
                </dl>
            </div>

            <div className="rounded-lg border border-neutral-200 bg-white p-4 text-center shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
                <div className="text-xs text-neutral-500 uppercase">
                    {t('convocations.room')}
                </div>
                <div className="text-2xl font-bold">{candidate.room}</div>
                <div className="text-sm text-neutral-500">
                    {candidate.building}
                </div>
                <div className="mt-2 text-lg font-semibold">
                    {t('convocations.seat', { seat: candidate.seat })}
                </div>
            </div>
        </>
    );
}
