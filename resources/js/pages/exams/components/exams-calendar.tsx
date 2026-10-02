import type { EventClickArg } from '@fullcalendar/core';
import frLocale from '@fullcalendar/core/locales/fr';
import dayGridPlugin from '@fullcalendar/daygrid';
import listPlugin from '@fullcalendar/list';
import FullCalendar from '@fullcalendar/react';
import timeGridPlugin from '@fullcalendar/timegrid';
import { useMemo } from 'react';
import { useTranslation } from '@/i18n/LanguageContext';
import { GRID_END, GRID_START } from '@/lib/scheduling-grid';
import type { Exam, ExamPeriod } from './types';

const PLUGINS = [dayGridPlugin, timeGridPlugin, listPlugin];
const LOCALES = [frLocale];
const TOOLBAR = {
    left: 'prev,next',
    center: 'title',
    right: 'timeGridWeek,dayGridMonth,listMonth',
};

/** The day after `date` (`Y-m-d`), as FullCalendar's exclusive range end. */
function dayAfter(date: string): string {
    const next = new Date(`${date}T00:00:00Z`);
    next.setUTCDate(next.getUTCDate() + 1);

    return next.toISOString().slice(0, 10);
}

interface ExamsCalendarProps {
    period: ExamPeriod;
    exams: Exam[];
    onExamClick: (exam: Exam) => void;
}

/** A read-only calendar of the period's exams; loaded only when the calendar view is opened. */
export default function ExamsCalendar({
    period,
    exams,
    onExamClick,
}: ExamsCalendarProps) {
    const { locale } = useTranslation();

    const events = useMemo(
        () =>
            exams.map((exam) => ({
                id: String(exam.id),
                title: `${exam.module.code} · ${exam.groups.map((group) => group.name).join(', ')}`,
                start: exam.start,
                end: exam.end,
                backgroundColor: exam.module.color_code,
                borderColor: exam.module.color_code,
                classNames: exam.state === 'draft' ? ['opacity-60'] : [],
                extendedProps: { exam },
            })),
        [exams],
    );

    const validRange = useMemo(
        () => ({ start: period.start_date, end: dayAfter(period.end_date) }),
        [period.start_date, period.end_date],
    );

    return (
        <div className="rounded-lg border border-neutral-200 bg-white p-3 shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
            <FullCalendar
                plugins={PLUGINS}
                locales={LOCALES}
                locale={locale}
                timeZone="UTC"
                initialView="timeGridWeek"
                initialDate={period.start_date}
                validRange={validRange}
                headerToolbar={TOOLBAR}
                slotMinTime={GRID_START}
                slotMaxTime={GRID_END}
                allDaySlot={false}
                firstDay={1}
                height="auto"
                events={events}
                eventClick={(arg: EventClickArg) =>
                    onExamClick(arg.event.extendedProps.exam as Exam)
                }
            />
        </div>
    );
}
