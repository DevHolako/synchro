import { Head, setLayoutProps } from '@inertiajs/react';
import { useEffect } from 'react';
import { useTranslation } from '@/i18n/LanguageContext';
import { dashboard } from '@/routes';
import { index as myGradesIndex } from '@/routes/my-grades';
import { OwnGradesTable } from './components/own-grades-table';
import type { OwnGrade } from './components/types';

/** The student's grades, published once each deliberation is locked. */
export default function MyGradesIndex({ grades }: { grades: OwnGrade[] }) {
    const { t } = useTranslation();

    useEffect(() => {
        setLayoutProps({
            breadcrumbs: [
                { title: t('nav.dashboard'), href: dashboard().url },
                { title: t('nav.my_grades'), href: myGradesIndex().url },
            ],
        });
    }, [t]);

    return (
        <>
            <Head title={t('my_grades.title')} />

            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        {t('my_grades.title')}
                    </h1>
                    <p className="text-sm text-neutral-500">
                        {t('my_grades.description')}
                    </p>
                </div>
                <OwnGradesTable grades={grades} />
            </div>
        </>
    );
}
