import { setLayoutProps } from '@inertiajs/react';
import { useEffect } from 'react';
import { useTranslation } from '@/i18n/LanguageContext';
import { dashboard } from '@/routes';
import { index as examsIndex } from '@/routes/exams';

/** Dashboard › Exams, for the exams page and the screens reached from it. */
export function useExamsBreadcrumbs(): void {
    const { t } = useTranslation();

    useEffect(() => {
        setLayoutProps({
            breadcrumbs: [
                { title: t('nav.dashboard'), href: dashboard().url },
                { title: t('nav.exams'), href: examsIndex().url },
            ],
        });
    }, [t]);
}
