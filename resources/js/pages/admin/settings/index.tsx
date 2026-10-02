import React, { useCallback } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Palette, RefreshCw, Save, Shield } from 'lucide-react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { applyThemeColor, applyThemeRadius } from '@/hooks/use-theme';
import { useTranslation } from '@/i18n/LanguageContext';
import { index as adminSettingsIndex, update as adminSettingsUpdate } from '@/routes/admin/settings';
import { ThemeColorSelector } from './components/theme-color-selector';
import { ThemeModeSelector } from './components/theme-mode-selector';
import { ThemePreviewCard } from './components/theme-preview-card';
import { ThemeRadiusSelector } from './components/theme-radius-selector';
import type { AdminSettingsPageProps, ThemeColor, ThemeMode, ThemeRadius } from './components/types';

export default function AdminSettingsPage({
    settings,
}: AdminSettingsPageProps) {
    const { t } = useTranslation();

    const { data, setData, put, processing } = useForm({
        theme_color: settings.theme_color,
        theme_radius: settings.theme_radius,
        theme_mode: settings.theme_mode,
    });

    const handleColorChange = useCallback(
        (color: ThemeColor) => {
            setData('theme_color', color);
            applyThemeColor(color);
        },
        [setData],
    );

    const handleRadiusChange = useCallback(
        (radius: ThemeRadius) => {
            setData('theme_radius', radius);
            applyThemeRadius(radius);
        },
        [setData],
    );

    const handleModeChange = useCallback(
        (mode: ThemeMode) => {
            setData('theme_mode', mode);
        },
        [setData],
    );

    const handleResetPreview = useCallback(() => {
        setData({
            theme_color: settings.theme_color,
            theme_radius: settings.theme_radius,
            theme_mode: settings.theme_mode,
        });
        applyThemeColor(settings.theme_color);
        applyThemeRadius(settings.theme_radius);
    }, [setData, settings]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(adminSettingsUpdate().url, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(t('admin_settings.save_success'));
            },
        });
    };

    return (
        <>
            <Head title={t('admin_settings.title')} />

            <div className="space-y-6 max-w-5xl mx-auto px-4 py-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex items-center gap-2">
                            <Badge variant="outline" className="border-primary/30 text-primary gap-1.5 py-0.5">
                                <Shield className="w-3.5 h-3.5" />
                                {t('admin_settings.badge')}
                            </Badge>
                        </div>
                        <Heading
                            title={t('admin_settings.title')}
                            description={t('admin_settings.description')}
                        />
                    </div>

                    <div className="flex items-center gap-2 self-start sm:self-auto">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={handleResetPreview}
                            disabled={processing}
                        >
                            <RefreshCw className="w-4 h-4 mr-1.5" />
                            {t('admin_settings.reset_preview')}
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            onClick={handleSubmit}
                            disabled={processing}
                        >
                            <Save className="w-4 h-4 mr-1.5" />
                            {processing
                                ? t('admin_settings.saving_button')
                                : t('admin_settings.save_button')}
                        </Button>
                    </div>
                </div>

                <Separator />

                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    <div className="lg:col-span-7 space-y-6">
                        <Card className="border border-border/80 shadow-sm">
                            <CardHeader>
                                <div className="flex items-center gap-2">
                                    <Palette className="w-5 h-5 text-primary" />
                                    <CardTitle className="text-lg">
                                        {t('admin_settings.theme_section_title')}
                                    </CardTitle>
                                </div>
                                <CardDescription>
                                    {t('admin_settings.theme_section_desc')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <ThemeColorSelector
                                    value={data.theme_color}
                                    onChange={handleColorChange}
                                />
                                <Separator />
                                <ThemeRadiusSelector
                                    value={data.theme_radius}
                                    onChange={handleRadiusChange}
                                />
                                <Separator />
                                <ThemeModeSelector
                                    value={data.theme_mode}
                                    onChange={handleModeChange}
                                />
                            </CardContent>
                        </Card>
                    </div>

                    <div className="lg:col-span-5 sticky top-6">
                        <ThemePreviewCard
                            color={data.theme_color}
                            radius={data.theme_radius}
                        />
                    </div>
                </div>
            </div>
        </>
    );
}

AdminSettingsPage.layout = {
    breadcrumbs: [
        {
            title: 'Paramètres système',
            href: adminSettingsIndex().url,
        },
    ],
};
