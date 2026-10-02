import { Link } from '@inertiajs/react';
import { ClipboardList } from 'lucide-react';
import { useTranslation } from '@/i18n/LanguageContext';
import { show as showGrades } from '@/routes/exams/grades';
import type { Exam } from './types';

/** The way to the exam's grade sheet, with its status once opened. */
export function ExamGradesLink({ exam }: { exam: Exam }) {
    const { t } = useTranslation();

    if (exam.grades === null) {
        return null;
    }

    const enter =
        exam.grades.can_enter &&
        exam.grades.status !== 'submitted' &&
        exam.grades.status !== 'locked';

    return (
        <div className="mt-1">
            <Link
                href={showGrades.url(exam.id)}
                className="inline-flex items-center gap-1 text-xs font-medium text-sky-700 hover:underline dark:text-sky-300"
            >
                <ClipboardList className="size-3.5" />
                {t(enter ? 'grades.enter' : 'grades.view')}
                {exam.grades.status ? (
                    <span className="text-neutral-500">
                        · {t(`grades.status_${exam.grades.status}`)}
                    </span>
                ) : null}
            </Link>
        </div>
    );
}
