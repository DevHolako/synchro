import type { LucideIcon } from 'lucide-react';
import { BookOpen, Building2, GraduationCap, UserCog } from 'lucide-react';
import { useTranslation } from '@/i18n/LanguageContext';
import { cn } from '@/lib/utils';
import type { ImportTypeDefinition, ImportTypeKey } from './types';

const TYPE_ICONS: Record<ImportTypeKey, LucideIcon> = {
    rooms: Building2,
    modules: BookOpen,
    teachers: UserCog,
    students: GraduationCap,
};

interface ImportTypePickerProps {
    types: ImportTypeDefinition[];
    selected: ImportTypeKey | null;
    onSelect: (type: ImportTypeKey) => void;
}

export function ImportTypePicker({
    types,
    selected,
    onSelect,
}: ImportTypePickerProps) {
    const { t } = useTranslation();

    return (
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            {types.map(({ type }) => {
                const Icon = TYPE_ICONS[type];
                const isSelected = selected === type;

                return (
                    <button
                        key={type}
                        type="button"
                        onClick={() => onSelect(type)}
                        aria-pressed={isSelected}
                        className={cn(
                            'flex items-start gap-3 rounded-lg border bg-white p-4 text-left shadow-xs transition-colors dark:bg-neutral-900',
                            isSelected
                                ? 'border-indigo-500 ring-2 ring-indigo-500/20'
                                : 'border-neutral-200 hover:border-neutral-300 dark:border-neutral-800 dark:hover:border-neutral-700',
                        )}
                    >
                        <Icon
                            className={cn(
                                'mt-0.5 size-5 shrink-0',
                                isSelected
                                    ? 'text-indigo-500'
                                    : 'text-neutral-400',
                            )}
                        />
                        <span>
                            <span className="block text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                {t(`imports.type_${type}`)}
                            </span>
                            <span className="block text-xs text-neutral-500">
                                {t(`imports.type_${type}_desc`)}
                            </span>
                        </span>
                    </button>
                );
            })}
        </div>
    );
}
