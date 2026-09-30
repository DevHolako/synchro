import { Languages } from 'lucide-react';
import React from 'react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';

export function LanguageSwitcher() {
    const { locale, setLocale, t } = useTranslation();

    return (
        <div className="flex items-center gap-1 rounded-lg border border-neutral-200 bg-neutral-100/60 p-0.5 text-xs dark:border-neutral-800 dark:bg-neutral-800/60">
            <span className="sr-only">{t('common.language')}</span>
            <Languages className="ml-1.5 size-3.5 text-neutral-500" />
            <Button
                variant={locale === 'fr' ? 'secondary' : 'ghost'}
                size="sm"
                className={`h-6 px-2 text-xs font-semibold ${
                    locale === 'fr'
                        ? 'bg-white shadow-xs dark:bg-neutral-700'
                        : 'text-neutral-500 hover:text-neutral-900 dark:hover:text-neutral-100'
                }`}
                onClick={() => setLocale('fr')}
                title={t('common.switch_to_fr')}
            >
                FR
            </Button>
            <Button
                variant={locale === 'en' ? 'secondary' : 'ghost'}
                size="sm"
                className={`h-6 px-2 text-xs font-semibold ${
                    locale === 'en'
                        ? 'bg-white shadow-xs dark:bg-neutral-700'
                        : 'text-neutral-500 hover:text-neutral-900 dark:hover:text-neutral-100'
                }`}
                onClick={() => setLocale('en')}
                title={t('common.switch_to_en')}
            >
                EN
            </Button>
        </div>
    );
}
