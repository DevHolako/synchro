import React, { memo } from 'react';
import { Check } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/i18n/LanguageContext';
import { cn } from '@/lib/utils';
import type { ColorDefinition, ThemeColor } from './types';

const COLOR_CONFIGS: ColorDefinition[] = [
    {
        id: 'indigo',
        nameKey: 'admin_settings.colors.indigo',
        bgClass: 'bg-indigo-600',
        borderClass: 'border-indigo-600',
        hexPreview: '#4f46e5',
    },
    {
        id: 'ocean',
        nameKey: 'admin_settings.colors.ocean',
        bgClass: 'bg-blue-600',
        borderClass: 'border-blue-600',
        hexPreview: '#2563eb',
    },
    {
        id: 'emerald',
        nameKey: 'admin_settings.colors.emerald',
        bgClass: 'bg-emerald-600',
        borderClass: 'border-emerald-600',
        hexPreview: '#059669',
    },
    {
        id: 'violet',
        nameKey: 'admin_settings.colors.violet',
        bgClass: 'bg-purple-600',
        borderClass: 'border-purple-600',
        hexPreview: '#7c3aed',
    },
    {
        id: 'rose',
        nameKey: 'admin_settings.colors.rose',
        bgClass: 'bg-rose-600',
        borderClass: 'border-rose-600',
        hexPreview: '#e11d48',
    },
    {
        id: 'amber',
        nameKey: 'admin_settings.colors.amber',
        bgClass: 'bg-amber-600',
        borderClass: 'border-amber-600',
        hexPreview: '#d97706',
    },
    {
        id: 'zinc',
        nameKey: 'admin_settings.colors.zinc',
        bgClass: 'bg-zinc-700',
        borderClass: 'border-zinc-700',
        hexPreview: '#3f3f46',
    },
];

interface ThemeColorSelectorProps {
    value: ThemeColor;
    onChange: (color: ThemeColor) => void;
}

export const ThemeColorSelector = memo(function ThemeColorSelector({
    value,
    onChange,
}: ThemeColorSelectorProps) {
    const { t } = useTranslation();

    return (
        <div className="space-y-3">
            <div>
                <Label className="text-base font-medium">
                    {t('admin_settings.color_label')}
                </Label>
                <p className="text-sm text-muted-foreground mt-0.5">
                    {t('admin_settings.color_desc')}
                </p>
            </div>

            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                {COLOR_CONFIGS.map((item) => {
                    const isSelected = value === item.id;

                    return (
                        <button
                            key={item.id}
                            type="button"
                            onClick={() => onChange(item.id)}
                            className={cn(
                                'flex items-center gap-3 p-3 rounded-lg border text-left transition-all',
                                'hover:border-primary/50 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2',
                                isSelected
                                    ? 'border-primary bg-primary/5 shadow-sm ring-1 ring-primary'
                                    : 'border-border bg-card',
                            )}
                            aria-pressed={isSelected}
                        >
                            <span
                                className={cn(
                                    'w-6 h-6 rounded-full flex items-center justify-center shrink-0 text-white shadow-sm',
                                    item.bgClass,
                                )}
                            >
                                {isSelected && <Check className="w-3.5 h-3.5" />}
                            </span>
                            <span className="text-sm font-medium leading-none truncate">
                                {t(item.nameKey)}
                            </span>
                        </button>
                    );
                })}
            </div>
        </div>
    );
});
