import React, { memo } from 'react';
import { Eye, Sparkles } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/i18n/LanguageContext';
import type { ThemeColor, ThemeRadius } from './types';

interface ThemePreviewCardProps {
    color: ThemeColor;
    radius: ThemeRadius;
}

export const ThemePreviewCard = memo(function ThemePreviewCard({
    color,
    radius,
}: ThemePreviewCardProps) {
    const { t } = useTranslation();

    return (
        <Card className="border border-border/80 shadow-sm overflow-hidden">
            <CardHeader className="bg-primary/5 border-b border-primary/10 pb-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <div className="p-1.5 rounded-md bg-primary text-primary-foreground shadow-sm">
                            <Sparkles className="w-4 h-4" />
                        </div>
                        <div>
                            <CardTitle className="text-base">
                                {t('admin_settings.preview_title')}
                            </CardTitle>
                            <CardDescription className="text-xs">
                                {t('admin_settings.preview_desc')}
                            </CardDescription>
                        </div>
                    </div>
                    <Badge variant="default" className="text-xs shadow-none">
                        {t(`admin_settings.colors.${color}`)} · {t(`admin_settings.radii.${radius}`)}
                    </Badge>
                </div>
            </CardHeader>

            <CardContent className="pt-6 space-y-6">
                <div className="p-4 rounded-lg bg-card border border-border space-y-3">
                    <div className="flex items-center justify-between">
                        <h4 className="text-sm font-semibold">
                            {t('admin_settings.preview_sample_card_title')}
                        </h4>
                        <Badge variant="outline" className="border-primary/40 text-primary">
                            {t('admin_settings.preview_sample_badge')}
                        </Badge>
                    </div>
                    <p className="text-xs text-muted-foreground leading-relaxed">
                        {t('admin_settings.preview_sample_card_desc')}
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-3">
                    <Button variant="default" size="sm">
                        <Eye className="w-4 h-4 mr-1.5" />
                        {t('admin_settings.preview_sample_button')}
                    </Button>
                    <Button variant="outline" size="sm">
                        {t('admin_settings.preview_sample_outline')}
                    </Button>
                    <Button variant="secondary" size="sm">
                        Secondary
                    </Button>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div className="space-y-1.5">
                        <label className="text-xs font-medium text-muted-foreground">
                            Focus Ring Preview
                        </label>
                        <Input
                            placeholder="Input with dynamic focus ring..."
                            className="text-xs"
                            readOnly
                        />
                    </div>
                    <div className="space-y-1.5">
                        <label className="text-xs font-medium text-muted-foreground">
                            Progress Bar Accent
                        </label>
                        <div className="h-9 flex items-center">
                            <div className="w-full bg-secondary h-2.5 rounded-full overflow-hidden">
                                <div className="bg-primary h-full w-2/3 rounded-full transition-all" />
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
});
