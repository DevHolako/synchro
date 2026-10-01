import { useForm } from '@inertiajs/react';
import { Upload } from 'lucide-react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/i18n/LanguageContext';
import { store } from '@/routes/imports';
import { ImportDropzone } from './import-dropzone';
import type { ImportLimits, ImportTypeKey } from './types';

interface ImportUploadFormProps {
    type: ImportTypeKey;
    limits: ImportLimits;
}

export function ImportUploadForm({ type, limits }: ImportUploadFormProps) {
    const { t } = useTranslation();
    const form = useForm<{ file: File | null }>({ file: null });

    const handleFileChange = (file: File | null) => {
        form.setData('file', file);
        form.clearErrors();
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        if (!form.data.file) {
            return;
        }

        form.post(store.url(type), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <form onSubmit={handleSubmit} className="flex flex-col gap-3">
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
    );
}
