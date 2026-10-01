import { Head, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import React, { useState } from 'react';

import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';
import { index, toggleActive } from '@/routes/modules';
import { ModuleDialog } from './components/module-dialog';
import { ModuleFilterBar } from './components/module-filter-bar';
import { ModuleStatsCards } from './components/module-stats';
import { ModulesTable } from './components/modules-table';
import type {
    Module,
    ModuleFilters,
    ModuleStats,
    Program,
    Teacher,
} from './components/types';

interface ModulesIndexProps {
    modules: Module[];
    programs: Program[];
    teachers: Teacher[];
    filters: ModuleFilters;
    stats: ModuleStats;
}

export default function ModulesIndex({
    modules,
    programs,
    teachers,
    filters,
    stats,
}: ModulesIndexProps) {
    const { t } = useTranslation();
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedProgram, setSelectedProgram] = useState(
        filters.program_id || '',
    );
    const [selectedTeacher, setSelectedTeacher] = useState(
        filters.teacher_id || '',
    );
    const [selectedStatus, setSelectedStatus] = useState(
        filters.is_active || 'all',
    );

    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [editingModule, setEditingModule] = useState<Module | null>(null);

    const handleApplyFilters = () => {
        router.get(
            index().url,
            {
                search: searchTerm || undefined,
                program_id: selectedProgram || undefined,
                teacher_id: selectedTeacher || undefined,
                is_active:
                    selectedStatus !== 'all' ? selectedStatus : undefined,
            },
            { preserveState: true },
        );
    };

    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedProgram('');
        setSelectedTeacher('');
        setSelectedStatus('all');
        router.get(index().url, {}, { preserveState: true });
    };

    const handleToggleActive = (module: Module) => {
        router.patch(toggleActive(module.id).url, {}, { preserveScroll: true });
    };

    return (
        <>
            <Head title={t('modules.title')} />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                            {t('modules.title')}
                        </h1>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            {t('modules.description')}
                        </p>
                    </div>

                    <Button
                        variant="default"
                        size="sm"
                        onClick={() => {
                            setEditingModule(null);
                            setIsDialogOpen(true);
                        }}
                    >
                        <Plus className="mr-1.5 size-4" />
                        {t('modules.new_module')}
                    </Button>
                </div>

                <ModuleStatsCards stats={stats} />

                <ModuleFilterBar
                    searchTerm={searchTerm}
                    onSearchChange={setSearchTerm}
                    selectedProgram={selectedProgram}
                    onProgramChange={setSelectedProgram}
                    selectedTeacher={selectedTeacher}
                    onTeacherChange={setSelectedTeacher}
                    selectedStatus={selectedStatus}
                    onStatusChange={setSelectedStatus}
                    programs={programs}
                    teachers={teachers}
                    onApply={handleApplyFilters}
                    onReset={handleResetFilters}
                />

                <ModulesTable
                    modules={modules}
                    onEdit={(module) => {
                        setEditingModule(module);
                        setIsDialogOpen(true);
                    }}
                    onToggleActive={handleToggleActive}
                    onResetFilters={handleResetFilters}
                />
            </div>

            <ModuleDialog
                open={isDialogOpen}
                onOpenChange={setIsDialogOpen}
                programs={programs}
                teachers={teachers}
                moduleToEdit={editingModule}
            />
        </>
    );
}
