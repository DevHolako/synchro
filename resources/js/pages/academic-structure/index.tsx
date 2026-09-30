import { Head, router } from '@inertiajs/react';
import React, { useState } from 'react';
import { toast } from 'sonner';

import { useTranslation } from '@/i18n/LanguageContext';
import { AcademicFilterBar } from './components/academic-filter-bar';
import { AcademicStatsCards } from './components/academic-stats';
import { AcademicTab, AcademicTabs } from './components/academic-tabs';
import { DepartmentDialog } from './components/department-dialog';
import { DepartmentsTable } from './components/departments-table';
import { ProgramDialog } from './components/program-dialog';
import { ProgramsTable } from './components/programs-table';
import { StudentGroupDialog } from './components/student-group-dialog';
import { StudentGroupsTable } from './components/student-groups-table';
import type {
    AcademicFilters,
    AcademicStats,
    Campus,
    Department,
    Program,
    StudentGroup,
} from './components/types';

interface AcademicIndexProps {
    departments: Department[];
    programs: Program[];
    studentGroups: StudentGroup[];
    campuses: Campus[];
    filters: AcademicFilters;
    stats: AcademicStats;
}

export default function AcademicStructureIndex({
    departments,
    programs,
    studentGroups,
    campuses,
    filters,
    stats,
}: AcademicIndexProps) {
    const { t } = useTranslation();
    const [activeTab, setActiveTab] = useState<AcademicTab>('programs');

    // Filter states
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedDepartment, setSelectedDepartment] = useState(
        filters.department_id || '',
    );
    const [selectedModality, setSelectedModality] = useState(
        filters.program_modality || 'all',
    );
    const [selectedStatus, setSelectedStatus] = useState(
        filters.is_active || 'all',
    );

    // Dialog states
    const [isDeptDialogOpen, setIsDeptDialogOpen] = useState(false);
    const [editingDept, setEditingDept] = useState<Department | null>(null);

    const [isProgDialogOpen, setIsProgDialogOpen] = useState(false);
    const [editingProg, setEditingProg] = useState<Program | null>(null);

    const [isGroupDialogOpen, setIsGroupDialogOpen] = useState(false);
    const [editingGroup, setEditingGroup] = useState<StudentGroup | null>(null);

    const handleApplyFilters = () => {
        router.get(
            '/academic-structure',
            {
                search: searchTerm || undefined,
                department_id: selectedDepartment || undefined,
                program_modality:
                    selectedModality !== 'all' ? selectedModality : undefined,
                is_active:
                    selectedStatus !== 'all' ? selectedStatus : undefined,
            },
            { preserveState: true },
        );
    };

    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedDepartment('');
        setSelectedModality('all');
        setSelectedStatus('all');
        router.get('/academic-structure', {}, { preserveState: true });
    };

    const handleToggleDeptActive = (dept: Department) => {
        router.patch(
            `/departments/${dept.id}/toggle-active`,
            {},
            {
                preserveScroll: true,
                onSuccess: () =>
                    toast.success(
                        t('academic.toast_dept_status_updated', {
                            name: dept.name,
                        }),
                    ),
            },
        );
    };

    const handleToggleProgActive = (prog: Program) => {
        router.patch(
            `/programs/${prog.id}/toggle-active`,
            {},
            {
                preserveScroll: true,
                onSuccess: () =>
                    toast.success(
                        t('academic.toast_prog_status_updated', {
                            name: prog.name,
                        }),
                    ),
            },
        );
    };

    const handleToggleGroupActive = (group: StudentGroup) => {
        router.patch(
            `/student-groups/${group.id}/toggle-active`,
            {},
            {
                preserveScroll: true,
                onSuccess: () =>
                    toast.success(
                        t('academic.toast_group_status_updated', {
                            name: group.name,
                        }),
                    ),
            },
        );
    };

    return (
        <>
            <Head title={t('academic.title')} />

            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                        {t('academic.title')}
                    </h1>
                    <p className="text-sm text-neutral-500 dark:text-neutral-400">
                        {t('academic.description')}
                    </p>
                </div>

                <AcademicStatsCards stats={stats} />

                <AcademicFilterBar
                    searchTerm={searchTerm}
                    onSearchChange={setSearchTerm}
                    selectedDepartment={selectedDepartment}
                    onDepartmentChange={setSelectedDepartment}
                    selectedModality={selectedModality}
                    onModalityChange={setSelectedModality}
                    selectedStatus={selectedStatus}
                    onStatusChange={setSelectedStatus}
                    departments={departments}
                    onApply={handleApplyFilters}
                    onReset={handleResetFilters}
                />

                <AcademicTabs
                    activeTab={activeTab}
                    onTabChange={setActiveTab}
                    departmentsCount={departments.length}
                    programsCount={programs.length}
                    groupsCount={studentGroups.length}
                    onNewDepartment={() => {
                        setEditingDept(null);
                        setIsDeptDialogOpen(true);
                    }}
                    onNewProgram={() => {
                        setEditingProg(null);
                        setIsProgDialogOpen(true);
                    }}
                    onNewGroup={() => {
                        setEditingGroup(null);
                        setIsGroupDialogOpen(true);
                    }}
                />

                {activeTab === 'departments' && (
                    <DepartmentsTable
                        departments={departments}
                        onEdit={(dept) => {
                            setEditingDept(dept);
                            setIsDeptDialogOpen(true);
                        }}
                        onToggleActive={handleToggleDeptActive}
                        onResetFilters={handleResetFilters}
                    />
                )}

                {activeTab === 'programs' && (
                    <ProgramsTable
                        programs={programs}
                        onEdit={(prog) => {
                            setEditingProg(prog);
                            setIsProgDialogOpen(true);
                        }}
                        onToggleActive={handleToggleProgActive}
                        onResetFilters={handleResetFilters}
                    />
                )}

                {activeTab === 'groups' && (
                    <StudentGroupsTable
                        studentGroups={studentGroups}
                        onEdit={(group) => {
                            setEditingGroup(group);
                            setIsGroupDialogOpen(true);
                        }}
                        onToggleActive={handleToggleGroupActive}
                        onResetFilters={handleResetFilters}
                    />
                )}
            </div>

            <DepartmentDialog
                open={isDeptDialogOpen}
                onOpenChange={setIsDeptDialogOpen}
                departmentToEdit={editingDept}
            />

            <ProgramDialog
                open={isProgDialogOpen}
                onOpenChange={setIsProgDialogOpen}
                departments={departments}
                programToEdit={editingProg}
            />

            <StudentGroupDialog
                open={isGroupDialogOpen}
                onOpenChange={setIsGroupDialogOpen}
                programs={programs}
                campuses={campuses}
                groupToEdit={editingGroup}
            />
        </>
    );
}
