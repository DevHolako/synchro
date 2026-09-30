import { Building2, Edit2, Power } from 'lucide-react';
import React, { memo } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Department } from './types';

interface DepartmentsTableProps {
    departments: Department[];
    onEdit: (department: Department) => void;
    onToggleActive: (department: Department) => void;
    onResetFilters: () => void;
}

const DepartmentRow = memo(function DepartmentRow({
    department,
    onEdit,
    onToggleActive,
}: {
    department: Department;
    onEdit: (department: Department) => void;
    onToggleActive: (department: Department) => void;
}) {
    return (
        <tr
            className={`transition-colors hover:bg-neutral-50/50 dark:hover:bg-neutral-800/40 ${
                !department.is_active ? 'bg-neutral-50/20 opacity-60' : ''
            }`}
        >
            <td className="px-6 py-4">
                <div className="font-semibold text-neutral-900 dark:text-neutral-100">
                    {department.name}
                </div>
                {department.description && (
                    <div className="line-clamp-1 text-xs text-neutral-500">
                        {department.description}
                    </div>
                )}
            </td>

            <td className="px-6 py-4">
                <span className="rounded bg-neutral-100 px-2 py-1 font-mono text-xs font-semibold dark:bg-neutral-800">
                    {department.code}
                </span>
            </td>

            <td className="px-6 py-4">
                <div className="flex items-center gap-1 text-sm font-medium">
                    <span className="text-neutral-900 dark:text-neutral-100">
                        {department.programs_count ?? 0}
                    </span>
                    <span className="text-xs text-neutral-500">programs</span>
                </div>
            </td>

            <td className="px-6 py-4">
                <div className="flex items-center gap-1 text-sm font-medium">
                    <span className="text-neutral-900 dark:text-neutral-100">
                        {department.student_groups_count ?? 0}
                    </span>
                    <span className="text-xs text-neutral-500">groups</span>
                </div>
            </td>

            <td className="px-6 py-4">
                {department.is_active ? (
                    <Badge
                        variant="outline"
                        className="border-emerald-500/30 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400"
                    >
                        Active
                    </Badge>
                ) : (
                    <Badge
                        variant="outline"
                        className="border-neutral-300 text-neutral-500"
                    >
                        Inactive
                    </Badge>
                )}
            </td>

            <td className="px-6 py-4 text-right">
                <div className="flex items-center justify-end gap-1">
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => onEdit(department)}
                        title="Edit department"
                    >
                        <Edit2 className="size-4" />
                    </Button>

                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => onToggleActive(department)}
                        title={department.is_active ? 'Deactivate' : 'Activate'}
                        className={
                            department.is_active
                                ? 'text-neutral-500 hover:text-red-600'
                                : 'text-emerald-600'
                        }
                    >
                        <Power className="size-4" />
                    </Button>
                </div>
            </td>
        </tr>
    );
});

export function DepartmentsTable({
    departments,
    onEdit,
    onToggleActive,
    onResetFilters,
}: DepartmentsTableProps) {
    if (departments.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-neutral-300 p-12 text-center dark:border-neutral-700">
                <Building2 className="size-12 text-neutral-400" />
                <h3 className="mt-4 text-base font-semibold text-neutral-900 dark:text-neutral-100">
                    No departments found
                </h3>
                <p className="mt-1 text-sm text-neutral-500">
                    No academic departments match the selected filters.
                </p>
                <Button
                    variant="outline"
                    size="sm"
                    className="mt-4"
                    onClick={onResetFilters}
                >
                    Clear filters
                </Button>
            </div>
        );
    }

    return (
        <div className="overflow-x-auto rounded-lg border border-neutral-200 bg-white shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
            <table className="w-full text-left text-sm">
                <thead className="border-b border-neutral-200 bg-neutral-50/50 text-xs font-medium text-neutral-500 dark:border-neutral-800 dark:bg-neutral-800/50 dark:text-neutral-400">
                    <tr>
                        <th className="px-6 py-3">Department Name</th>
                        <th className="px-6 py-3">Code</th>
                        <th className="px-6 py-3">Programs</th>
                        <th className="px-6 py-3">Groups</th>
                        <th className="px-6 py-3">Status</th>
                        <th className="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                    {departments.map((dept) => (
                        <DepartmentRow
                            key={dept.id}
                            department={dept}
                            onEdit={onEdit}
                            onToggleActive={onToggleActive}
                        />
                    ))}
                </tbody>
            </table>
        </div>
    );
}
