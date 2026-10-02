import React, { memo } from 'react';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/i18n/LanguageContext';
import { cn } from '@/lib/utils';
import type { ThemeRadius } from './types';

const RADII_OPTIONS: Array<{
    id: ThemeRadius;
    nameKey: string;
    previewClass: string;
}> = [
    {
        id: 'sm',
        nameKey: 'admin_settings.radii.sm',
        previewClass: 'rounded-sm',
    },
    {
        id: 'md',
        nameKey: 'admin_settings.radii.md',
        previewClass: 'rounded-md',
    },
    {
        id: 'lg',
        nameKey: 'admin_settings.radii.lg',
        previewClass: 'rounded-xl',
    },
];

interface ThemeRadiusSelectorProps {
    value: ThemeRadius;
    onChange: (radius: ThemeRadius) => void;
}

export const ThemeRadiusSelector = memo(function ThemeRadiusSelector({
    value,
    onChange,
}: ThemeRadiusSelectorProps) {
    const { t } = useTranslation();

    return (
        <div className="space-y-3">
            <div>
                <Label className="text-base font-medium">
                    {t('admin_settings.radius_label')}
                </Label>
                <p className="text-sm text-muted-foreground mt-0.5">
                    {t('admin_settings.radius_desc')}
                </p>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                {RADII_OPTIONS.map((item) => {
                    const isSelected = value === item.id;

                    return (
                        <button
                            key={item.id}
                            type="button"
                            onClick={() => onChange(item.id)}
                            className={cn(
                                'flex items-center justify-between p-3.5 rounded-lg border text-left transition-all',
                                'hover:border-primary/50 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2',
                                isSelected
                                    ? 'border-primary bg-primary/5 shadow-sm ring-1 ring-primary'
                                    : 'border-border bg-card',
                            )}
                            aria-pressed={isSelected}
                        >
                            <span className="text-sm font-medium">
                                {t(item.nameKey)}
                            </span>
                            <div
                                className={cn(
                                    'w-6 h-6 border-2 border-primary bg-primary/20 transition-all',
                                    item.previewClass,
                                )}
                            />
                        </button>
                    );
                })}
            </div>
        </div>
    );
});
