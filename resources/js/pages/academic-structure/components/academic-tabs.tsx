import { BookOpen, Building2, Plus, Users } from 'lucide-react';
import React from 'react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';

export type AcademicTab = 'departments' | 'programs' | 'groups';

interface AcademicTabsProps {
    activeTab: AcademicTab;
    onTabChange: (tab: AcademicTab) => void;
    departmentsCount: number;
    programsCount: number;
    groupsCount: number;
    onNewDepartment: () => void;
    onNewProgram: () => void;
    onNewGroup: () => void;
}

export function AcademicTabs({
    activeTab,
    onTabChange,
    departmentsCount,
    programsCount,
    groupsCount,
    onNewDepartment,
    onNewProgram,
    onNewGroup,
}: AcademicTabsProps) {
    const { t } = useTranslation();

    return (
        <div className="flex flex-col gap-4 border-b border-neutral-200 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800">
            <div className="flex items-center gap-2">
                <button
                    type="button"
                    onClick={() => onTabChange('departments')}
                    className={`flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-colors ${
                        activeTab === 'departments'
                            ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900'
                            : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800'
                    }`}
                >
                    <Building2 className="size-4" />
                    {t('academic.tab_departments')}
                    <span className="ml-1 rounded-full bg-neutral-200 px-2 py-0.5 text-xs text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200">
                        {departmentsCount}
                    </span>
                </button>

                <button
                    type="button"
                    onClick={() => onTabChange('programs')}
                    className={`flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-colors ${
                        activeTab === 'programs'
                            ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900'
                            : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800'
                    }`}
                >
                    <BookOpen className="size-4" />
                    {t('academic.tab_programs')}
                    <span className="ml-1 rounded-full bg-neutral-200 px-2 py-0.5 text-xs text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200">
                        {programsCount}
                    </span>
                </button>

                <button
                    type="button"
                    onClick={() => onTabChange('groups')}
                    className={`flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-colors ${
                        activeTab === 'groups'
                            ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900'
                            : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800'
                    }`}
                >
                    <Users className="size-4" />
                    {t('academic.tab_student_groups')}
                    <span className="ml-1 rounded-full bg-neutral-200 px-2 py-0.5 text-xs text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200">
                        {groupsCount}
                    </span>
                </button>
            </div>

            <div className="flex items-center gap-2">
                <Button variant="outline" size="sm" onClick={onNewDepartment}>
                    <Plus className="mr-1.5 size-4" />
                    {t('academic.new_department')}
                </Button>
                <Button variant="outline" size="sm" onClick={onNewProgram}>
                    <Plus className="mr-1.5 size-4" />
                    {t('academic.new_program')}
                </Button>
                <Button variant="default" size="sm" onClick={onNewGroup}>
                    <Plus className="mr-1.5 size-4" />
                    {t('academic.new_student_group')}
                </Button>
            </div>
        </div>
    );
}
