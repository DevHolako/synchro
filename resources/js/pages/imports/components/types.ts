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

export type ImportReport =
    | {
          status: 'succeeded';
          type: ImportTypeKey;
          file: string;
          imported: number;
      }
    | {
          status: 'failed';
          type: ImportTypeKey;
          file: string;
          total_errors: number;
          errors: ImportRowError[];
      };

export const ACCOUNT_IMPORT_TYPES: readonly ImportTypeKey[] = [
    'teachers',
    'students',
];
