import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';

export function applyThemeColor(color: string): void {
    if (typeof document !== 'undefined') {
        document.documentElement.setAttribute('data-theme', color);
    }
}

export function applyThemeRadius(radius: string): void {
    if (typeof document !== 'undefined') {
        document.documentElement.setAttribute('data-radius', radius);
    }
}

export function useTheme() {
    const { props } = usePage();
    const theme = props.theme as { color: string; radius: string; mode: string } | undefined;

    useEffect(() => {
        if (theme?.color) {
            applyThemeColor(theme.color);
        }
        if (theme?.radius) {
            applyThemeRadius(theme.radius);
        }
    }, [theme?.color, theme?.radius]);

    return {
        theme: theme ?? { color: 'indigo', radius: 'md', mode: 'system' },
        applyThemeColor,
        applyThemeRadius,
    };
}
