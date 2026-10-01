import { FileSpreadsheet, UploadCloud, X } from 'lucide-react';
import type { ChangeEvent, DragEvent } from 'react';
import { useRef, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';
import { cn } from '@/lib/utils';
import type { ImportLimits } from './types';

const ACCEPTED_EXTENSIONS = ['csv', 'xlsx'];

function isAccepted(file: File): boolean {
    const extension = file.name.split('.').pop()?.toLowerCase() ?? '';

    return ACCEPTED_EXTENSIONS.includes(extension);
}

interface ImportDropzoneProps {
    file: File | null;
    limits: ImportLimits;
    error?: string;
    onFileChange: (file: File | null) => void;
}

export function ImportDropzone({
    file,
    limits,
    error,
    onFileChange,
}: ImportDropzoneProps) {
    const { t } = useTranslation();
    const inputRef = useRef<HTMLInputElement>(null);
    const [isDragging, setIsDragging] = useState(false);

    const accept = (candidate: File | undefined) => {
        if (!candidate) {
            return;
        }

        if (!isAccepted(candidate)) {
            toast.error(t('imports.dropzone_invalid'));

            return;
        }

        onFileChange(candidate);
    };

    const handleDrop = (e: DragEvent<HTMLButtonElement>) => {
        e.preventDefault();
        setIsDragging(false);
        accept(e.dataTransfer.files[0]);
    };

    const handleInput = (e: ChangeEvent<HTMLInputElement>) => {
        accept(e.target.files?.[0]);
        e.target.value = '';
    };

    if (file) {
        return (
            <div className="flex items-center gap-3 rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                <FileSpreadsheet className="size-8 text-emerald-500" />
                <div className="min-w-0 flex-1">
                    <div className="truncate text-sm font-medium text-neutral-900 dark:text-neutral-100">
                        {file.name}
                    </div>
                    <div className="text-xs text-neutral-500">
                        {Math.ceil(file.size / 1024)} KB
                    </div>
                </div>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    onClick={() => onFileChange(null)}
                    title={t('imports.remove_file')}
                    aria-label={t('imports.remove_file')}
                >
                    <X className="size-4" />
                </Button>
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-1">
            <button
                type="button"
                onClick={() => inputRef.current?.click()}
                onDragOver={(e) => {
                    e.preventDefault();
                    setIsDragging(true);
                }}
                onDragLeave={() => setIsDragging(false)}
                onDrop={handleDrop}
                className={cn(
                    'flex flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed p-10 text-center transition-colors',
                    isDragging
                        ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/20'
                        : 'border-neutral-300 hover:border-neutral-400 dark:border-neutral-700',
                )}
            >
                <UploadCloud className="size-10 text-neutral-400" />
                <span className="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                    {t('imports.dropzone_title')}
                </span>
                <span className="text-xs text-neutral-500">
                    {t('imports.dropzone_hint', {
                        rows: limits.max_rows,
                        size: Math.round(limits.max_kilobytes / 1024),
                    })}
                </span>
            </button>
            <input
                ref={inputRef}
                type="file"
                accept=".csv,.xlsx"
                className="hidden"
                onChange={handleInput}
            />
            {error && <p className="text-xs text-red-500">{error}</p>}
        </div>
    );
}
