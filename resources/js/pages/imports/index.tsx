import { Head, router, setLayoutProps, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';
import { useTranslation } from '@/i18n/LanguageContext';
import { dashboard } from '@/routes';
import { index } from '@/routes/imports';
import { ImportColumnsGuide } from './components/import-columns-guide';
import { ImportHistoryTable } from './components/import-history-table';
import { ImportReport } from './components/import-report';
import { ImportTypePicker } from './components/import-type-picker';
import { ImportUploadForm } from './components/import-upload-form';
import type {
    ImportFailureReport,
    ImportLimits,
    ImportTypeDefinition,
    ImportTypeKey,
    SpreadsheetImport,
} from './components/types';
import { useImportPolling } from './components/use-import-polling';

interface ImportsIndexProps {
    types: ImportTypeDefinition[];
    imports: SpreadsheetImport[];
    report?: ImportFailureReport | null;
    limits: ImportLimits;
}

/**
 * Loads one failed import's error report through the optional `report` prop.
 */
function loadReport(id: number): void {
    router.reload({
        only: ['report'],
        data: { report: id },
        preserveUrl: true,
        async: true,
    });
}

export default function ImportsIndex({
    types,
    imports,
    report,
    limits,
}: ImportsIndexProps) {
    const { t } = useTranslation();
    const userId = usePage().props.auth.user.id;
    const [selectedType, setSelectedType] = useState<ImportTypeKey | null>(
        types[0]?.type ?? null,
    );
    const [selectedImportId, setSelectedImportId] = useState<number | null>(
        null,
    );
    const definition = types.find((type) => type.type === selectedType);

    useEffect(() => {
        setLayoutProps({
            breadcrumbs: [
                { title: t('nav.dashboard'), href: dashboard().url },
                { title: t('nav.imports'), href: index().url },
            ],
        });
    }, [t]);

    const handleFinished = useCallback(
        (record: SpreadsheetImport) => {
            if (record.status === 'succeeded') {
                toast.success(
                    t('imports.toast_finished_success', {
                        file: record.original_filename,
                        count: record.imported_count,
                    }),
                );

                return;
            }

            toast.error(
                t('imports.toast_finished_failed', {
                    file: record.original_filename,
                    count: record.error_count,
                }),
            );
            setSelectedImportId(record.id);
            loadReport(record.id);
        },
        [t],
    );

    useImportPolling(imports, userId, handleFinished);

    const handleToggleImport = useCallback(
        (id: number) => {
            if (id === selectedImportId) {
                setSelectedImportId(null);

                return;
            }

            setSelectedImportId(id);
            loadReport(id);
        },
        [selectedImportId],
    );

    return (
        <>
            <Head title={t('imports.title')} />

            <div className="flex h-full w-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                        {t('imports.title')}
                    </h1>
                    <p className="max-w-3xl text-sm text-neutral-500 dark:text-neutral-400">
                        {t('imports.description')}
                    </p>
                </div>

                <section className="flex flex-col gap-3">
                    <h2 className="text-sm font-semibold text-neutral-700 dark:text-neutral-300">
                        {t('imports.step_type')}
                    </h2>
                    <ImportTypePicker
                        types={types}
                        selected={selectedType}
                        onSelect={setSelectedType}
                    />
                    {definition && (
                        <ImportColumnsGuide definition={definition} />
                    )}
                </section>

                {definition && (
                    <ImportUploadForm
                        key={definition.type}
                        type={definition.type}
                        limits={limits}
                    />
                )}

                <ImportHistoryTable
                    imports={imports}
                    selectedId={selectedImportId}
                    onToggle={handleToggleImport}
                />

                {report && report.id === selectedImportId && (
                    <ImportReport report={report} />
                )}
            </div>
        </>
    );
}
