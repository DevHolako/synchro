import { FileDown, Hourglass } from 'lucide-react';
import { useTranslation } from '@/i18n/LanguageContext';
import { convocation, roster } from '@/routes/exams';
import type { Exam } from './types';

const LINK_CLASS =
    'inline-flex items-center gap-1 text-xs font-medium text-sky-700 hover:underline dark:text-sky-300';
const PENDING_CLASS = 'inline-flex items-center gap-1 text-xs text-neutral-500';

/** The exam's PDFs the viewer may download: their convocation, or the room sheets. */
export function ExamDocuments({ exam }: { exam: Exam }) {
    const { t } = useTranslation();
    const sitsIt = exam.my_seat !== null && !exam.is_editable;

    if (!sitsIt && exam.roster === null) {
        return null;
    }

    return (
        <div className="mt-1 flex flex-wrap gap-3">
            {sitsIt && exam.my_seat?.convocation_ready ? (
                <a href={convocation.url(exam.id)} className={LINK_CLASS}>
                    <FileDown className="size-3.5" />
                    {t('exams.download_convocation')}
                </a>
            ) : null}
            {sitsIt && !exam.my_seat?.convocation_ready ? (
                <span className={PENDING_CLASS}>
                    <Hourglass className="size-3.5" />
                    {t('exams.convocation_pending')}
                </span>
            ) : null}
            {exam.roster === 'ready' ? (
                <a href={roster.url(exam.id)} className={LINK_CLASS}>
                    <FileDown className="size-3.5" />
                    {t('exams.download_roster')}
                </a>
            ) : null}
            {exam.roster === 'pending' ? (
                <span className={PENDING_CLASS}>
                    <Hourglass className="size-3.5" />
                    {t('exams.roster_pending')}
                </span>
            ) : null}
        </div>
    );
}
