import { Head, setLayoutProps, useForm } from '@inertiajs/react';
import { Upload } from 'lucide-react';
import type { FormEvent } from 'react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/i18n/LanguageContext';
import { dashboard } from '@/routes';
import { index, store } from '@/routes/imports';
import { ImportColumnsGuide } from './components/import-columns-guide';
import { ImportDropzone } from './components/import-dropzone';
import { ImportReport } from './components/import-report';
import { ImportTypePicker } from './components/import-type-picker';
import type {
    ImportLimits,
    ImportReport as ImportReportData,
    ImportTypeDefinition,
    ImportTypeKey,
} from './components/types';

interface ImportsIndexProps {
    types: ImportTypeDefinition[];
    limits: ImportLimits;
}

export default function ImportsIndex({ types, limits }: ImportsIndexProps) {
    const { t } = useTranslation();
    const [selectedType, setSelectedType] = useState<ImportTypeKey | null>(
        types[0]?.type ?? null,
    );
    const [report, setReport] = useState<ImportReportData | null>(null);
    const form = useForm<{ file: File | null }>({ file: null });
    const definition = types.find((type) => type.type === selectedType);

    useEffect(() => {
        setLayoutProps({
            breadcrumbs: [
                { title: t('nav.dashboard'), href: dashboard().url },
                { title: t('nav.imports'), href: index().url },
            ],
        });
    }, [t]);

    const handleSelectType = (type: ImportTypeKey) => {
        setSelectedType(type);
        setReport(null);
        form.reset();
        form.clearErrors();
    };

    const handleFileChange = (file: File | null) => {
        form.setData('file', file);
        form.clearErrors();
        setReport(null);
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        if (!selectedType || !form.data.file) {
            return;
        }

        form.post(store.url(selectedType), {
            forceFormData: true,
            preserveScroll: true,
            onFlash: (flash) =>
                setReport(
                    (flash.import_report as ImportReportData | undefined) ??
                        null,
                ),
            onSuccess: () => form.reset(),
        });
    };

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
                        onSelect={handleSelectType}
                    />
                    {definition && (
                        <ImportColumnsGuide definition={definition} />
                    )}
                </section>

                {definition && (
                    <form
                        onSubmit={handleSubmit}
                        className="flex flex-col gap-3"
                    >
                        <h2 className="text-sm font-semibold text-neutral-700 dark:text-neutral-300">
                            {t('imports.step_file')}
                        </h2>
                        <ImportDropzone
                            file={form.data.file}
                            limits={limits}
                            error={form.errors.file}
                            onFileChange={handleFileChange}
                        />
                        <div className="flex justify-end">
                            <Button
                                type="submit"
                                disabled={!form.data.file || form.processing}
                            >
                                {form.processing ? (
                                    <Spinner />
                                ) : (
                                    <Upload className="mr-1.5 size-4" />
                                )}
                                {t('imports.submit')}
                            </Button>
                        </div>
                    </form>
                )}

                {report && <ImportReport report={report} />}
            </div>
        </>
    );
}
