import { Head, setLayoutProps } from '@inertiajs/react';
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
    ImportLimits,
    ImportTypeDefinition,
    ImportTypeKey,
    SpreadsheetImport,
} from './components/types';
import { useImportPolling } from './components/use-import-polling';

interface ImportsIndexProps {
    types: ImportTypeDefinition[];
    imports: SpreadsheetImport[];
    limits: ImportLimits;
}

export default function ImportsIndex({
    types,
    imports,
    limits,
}: ImportsIndexProps) {
    const { t } = useTranslation();
    const [selectedType, setSelectedType] = useState<ImportTypeKey | null>(
        types[0]?.type ?? null,
    );
    const [selectedImportId, setSelectedImportId] = useState<number | null>(
        null,
    );
    const definition = types.find((type) => type.type === selectedType);
    const selectedImport = imports.find((i) => i.id === selectedImportId);

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
        },
        [t],
    );

    useImportPolling(imports, handleFinished);

    const handleToggleImport = useCallback(
        (id: number) =>
            setSelectedImportId((current) => (current === id ? null : id)),
        [],
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

                {selectedImport && <ImportReport report={selectedImport} />}
            </div>
        </>
    );
}
