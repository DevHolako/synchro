export type ImportTypeKey = 'rooms' | 'modules' | 'teachers' | 'students';

export interface ImportTypeDefinition {
    type: ImportTypeKey;
    columns: string[];
    required: string[];
}

export interface ImportLimits {
    max_rows: number;
    max_kilobytes: number;
}

export interface ImportRowError {
    row: number;
    column: string | null;
    message: string;
}

export type ImportStatus = 'pending' | 'processing' | 'succeeded' | 'failed';

export interface SpreadsheetImport {
    id: number;
    type: ImportTypeKey;
    status: ImportStatus;
    original_filename: string;
    imported_count: number;
    error_count: number;
    errors: ImportRowError[] | null;
    created_at: string;
    finished_at: string | null;
    user: { id: number; name: string } | null;
}

export const ACTIVE_IMPORT_STATUSES: readonly ImportStatus[] = [
    'pending',
    'processing',
];

export const ACCOUNT_IMPORT_TYPES: readonly ImportTypeKey[] = [
    'teachers',
    'students',
];
