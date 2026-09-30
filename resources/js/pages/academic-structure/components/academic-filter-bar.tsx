import { Filter, RotateCcw, Search } from 'lucide-react';
import React from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/i18n/LanguageContext';
import type { Department } from './types';

interface AcademicFilterBarProps {
    searchTerm: string;
    onSearchChange: (value: string) => void;
    selectedDepartment: string;
    onDepartmentChange: (value: string) => void;
    selectedModality: string;
    onModalityChange: (value: string) => void;
    selectedStatus: string;
    onStatusChange: (value: string) => void;
    departments: Department[];
    onApply: () => void;
    onReset: () => void;
}

export function AcademicFilterBar({
    searchTerm,
    onSearchChange,
    selectedDepartment,
    onDepartmentChange,
    selectedModality,
    onModalityChange,
    selectedStatus,
    onStatusChange,
    departments,
    onApply,
    onReset,
}: AcademicFilterBarProps) {
    const { t } = useTranslation();

    return (
        <div className="flex flex-col gap-4 rounded-lg border border-neutral-200 bg-white p-4 shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
            <div className="flex flex-wrap items-center gap-3">
                <div className="relative min-w-[220px] flex-1">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-400" />
                    <Input
                        placeholder={t('academic.filter_search_placeholder')}
                        value={searchTerm}
                        onChange={(e) => onSearchChange(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && onApply()}
                        className="pl-9"
                    />
                </div>

                <div className="w-[200px]">
                    <select
                        value={selectedDepartment}
                        onChange={(e) => onDepartmentChange(e.target.value)}
                        className="w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <option value="">
                            {t('academic.all_departments')}
                        </option>
                        {departments.map((dept) => (
                            <option key={dept.id} value={dept.id}>
                                {dept.code} - {dept.name}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="w-[180px]">
                    <select
                        value={selectedModality}
                        onChange={(e) => onModalityChange(e.target.value)}
                        className="w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <option value="all">
                            {t('academic.all_modalities')}
                        </option>
                        <option value="formation_initiale">
                            {t('academic.modality_initiale')}
                        </option>
                        <option value="temps_amenage">
                            {t('academic.modality_amenage')}
                        </option>
                    </select>
                </div>

                <div className="w-[140px]">
                    <select
                        value={selectedStatus}
                        onChange={(e) => onStatusChange(e.target.value)}
                        className="w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <option value="all">{t('common.all_statuses')}</option>
                        <option value="1">{t('common.active_only')}</option>
                        <option value="0">{t('common.inactive_only')}</option>
                    </select>
                </div>

                <Button variant="default" size="sm" onClick={onApply}>
                    <Filter className="mr-1.5 size-4" />
                    {t('common.apply_filters')}
                </Button>

                <Button variant="ghost" size="sm" onClick={onReset}>
                    <RotateCcw className="mr-1.5 size-4" />
                    {t('common.reset')}
                </Button>
            </div>
        </div>
    );
}
