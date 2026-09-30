import React, { createContext, useCallback, useContext, useState } from 'react';
import { en } from './en';
import { fr } from './fr';
import type { Locale, Translations } from './types';

const dictionaries: Record<Locale, Translations> = {
    fr,
    en,
};

interface LanguageContextType {
    locale: Locale;
    setLocale: (locale: Locale) => void;
    t: (key: string, params?: Record<string, string | number>) => string;
}

const LanguageContext = createContext<LanguageContextType | undefined>(
    undefined,
);

const STORAGE_KEY = 'synchro_locale';

export function LanguageProvider({
    children,
    defaultLocale = 'fr',
}: {
    children: React.ReactNode;
    defaultLocale?: Locale;
}) {
    const [locale, setLocaleState] = useState<Locale>(() => {
        if (typeof window !== 'undefined') {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved === 'fr' || saved === 'en') {
                return saved;
            }
        }
        return defaultLocale;
    });

    const setLocale = useCallback((newLocale: Locale) => {
        setLocaleState(newLocale);
        if (typeof window !== 'undefined') {
            localStorage.setItem(STORAGE_KEY, newLocale);
        }
    }, []);

    const t = useCallback(
        (key: string, params?: Record<string, string | number>): string => {
            const currentDict = dictionaries[locale] || dictionaries.fr;
            const keys = key.split('.');

            let result: unknown = currentDict;
            for (const k of keys) {
                if (result && typeof result === 'object' && k in result) {
                    result = (result as Record<string, unknown>)[k];
                } else {
                    // Fallback to French if not found in current dictionary
                    let fallbackResult: unknown = dictionaries.fr;
                    for (const fbKey of keys) {
                        if (
                            fallbackResult &&
                            typeof fallbackResult === 'object' &&
                            fbKey in fallbackResult
                        ) {
                            fallbackResult = (
                                fallbackResult as Record<string, unknown>
                            )[fbKey];
                        } else {
                            fallbackResult = undefined;
                            break;
                        }
                    }
                    result = fallbackResult ?? key;
                    break;
                }
            }

            if (typeof result !== 'string') {
                return key;
            }

            if (!params) {
                return result;
            }

            return Object.entries(params).reduce((str, [paramKey, val]) => {
                return str.replaceAll(`{${paramKey}}`, String(val));
            }, result);
        },
        [locale],
    );

    return (
        <LanguageContext.Provider value={{ locale, setLocale, t }}>
            {children}
        </LanguageContext.Provider>
    );
}

export function useTranslation() {
    const context = useContext(LanguageContext);
    if (!context) {
        throw new Error(
            'useTranslation must be used within a LanguageProvider',
        );
    }
    return context;
}
