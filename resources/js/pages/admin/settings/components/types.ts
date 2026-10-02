export type ThemeColor =
    | 'indigo'
    | 'ocean'
    | 'emerald'
    | 'violet'
    | 'rose'
    | 'amber'
    | 'zinc';

export type ThemeRadius = 'sm' | 'md' | 'lg';

export type ThemeMode = 'light' | 'dark' | 'system';

export interface AdminSettings {
    theme_color: ThemeColor;
    theme_radius: ThemeRadius;
    theme_mode: ThemeMode;
}

export interface AdminSettingsPageProps {
    settings: AdminSettings;
    availableColors: ThemeColor[];
    availableRadii: ThemeRadius[];
    availableModes: ThemeMode[];
}

export interface ColorDefinition {
    id: ThemeColor;
    nameKey: string;
    bgClass: string;
    borderClass: string;
    hexPreview: string;
}
