import { Download, MailCheck } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';
import { template } from '@/routes/imports';
import type { ImportTypeDefinition } from './types';
import { ACCOUNT_IMPORT_TYPES } from './types';

export function ImportColumnsGuide({
    definition,
}: {
    definition: ImportTypeDefinition;
}) {
    const { t } = useTranslation();

    return (
        <div className="flex flex-col gap-4 rounded-lg border border-neutral-200 bg-white p-4 shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                        {t('imports.columns_title')}
                    </h2>
                    <p className="text-xs text-neutral-500">
                        {t('imports.columns_desc')}
                    </p>
                </div>
                <Button variant="outline" size="sm" asChild>
                    <a href={template.url(definition.type)} download>
                        <Download className="mr-1.5 size-4" />
                        {t('imports.download_template')}
                    </a>
                </Button>
            </div>

            <ul className="grid gap-x-6 gap-y-2 sm:grid-cols-2 lg:grid-cols-3">
                {definition.columns.map((column) => (
                    <li key={column} className="flex flex-col gap-0.5">
                        <span className="flex items-center gap-2">
                            <code className="font-mono text-xs font-semibold text-neutral-900 dark:text-neutral-100">
                                {column}
                            </code>
                            {definition.required.includes(column) ? (
                                <Badge
                                    variant="outline"
                                    className="border-rose-500/30 text-[10px] text-rose-700 dark:text-rose-400"
                                >
                                    {t('imports.required')}
                                </Badge>
                            ) : (
                                <span className="text-[10px] text-neutral-400">
                                    {t('imports.optional')}
                                </span>
                            )}
                        </span>
                        <span className="text-xs text-neutral-500">
                            {t(`imports.hint_${column}`)}
                        </span>
                    </li>
                ))}
            </ul>

            {ACCOUNT_IMPORT_TYPES.includes(definition.type) && (
                <p className="flex items-start gap-2 rounded-md bg-sky-50 p-3 text-xs text-sky-800 dark:bg-sky-950/30 dark:text-sky-300">
                    <MailCheck className="mt-0.5 size-4 shrink-0" />
                    {t('imports.invitations_notice')}
                </p>
            )}
        </div>
    );
}
