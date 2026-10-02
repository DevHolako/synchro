import React, { memo } from 'react';
import { Laptop, Moon, Sun } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/i18n/LanguageContext';
import { cn } from '@/lib/utils';
import type { ThemeMode } from './types';

const MODE_OPTIONS: Array<{
    id: ThemeMode;
    nameKey: string;
    icon: typeof Sun;
}> = [
    {
        id: 'light',
        nameKey: 'admin_settings.modes.light',
        icon: Sun,
    },
    {
        id: 'dark',
        nameKey: 'admin_settings.modes.dark',
        icon: Moon,
    },
    {
        id: 'system',
        nameKey: 'admin_settings.modes.system',
        icon: Laptop,
    },
];

interface ThemeModeSelectorProps {
    value: ThemeMode;
    onChange: (mode: ThemeMode) => void;
}

export const ThemeModeSelector = memo(function ThemeModeSelector({
    value,
    onChange,
}: ThemeModeSelectorProps) {
    const { t } = useTranslation();

    return (
        <div className="space-y-3">
            <div>
                <Label className="text-base font-medium">
                    {t('admin_settings.mode_label')}
                </Label>
                <p className="text-sm text-muted-foreground mt-0.5">
                    {t('admin_settings.mode_desc')}
                </p>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                {MODE_OPTIONS.map((item) => {
                    const isSelected = value === item.id;
                    const Icon = item.icon;

                    return (
                        <button
                            key={item.id}
                            type="button"
                            onClick={() => onChange(item.id)}
                            className={cn(
                                'flex items-center gap-3 p-3.5 rounded-lg border text-left transition-all',
                                'hover:border-primary/50 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2',
                                isSelected
                                    ? 'border-primary bg-primary/5 shadow-sm ring-1 ring-primary'
                                    : 'border-border bg-card',
                            )}
                            aria-pressed={isSelected}
                        >
                            <Icon
                                className={cn(
                                    'w-4 h-4 shrink-0',
                                    isSelected
                                        ? 'text-primary'
                                        : 'text-muted-foreground',
                                )}
                            />
                            <span className="text-sm font-medium">
                                {t(item.nameKey)}
                            </span>
                        </button>
                    );
                })}
            </div>
        </div>
    );
});
