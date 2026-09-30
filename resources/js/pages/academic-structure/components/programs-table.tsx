import { Clock, Edit2, GraduationCap, Power, Sun, Users } from 'lucide-react';
import React, { memo } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Program } from './types';

interface ProgramsTableProps {
    programs: Program[];
    onEdit: (program: Program) => void;
    onToggleActive: (program: Program) => void;
    onResetFilters: () => void;
}

const ProgramRow = memo(function ProgramRow({
    program,
    onEdit,
    onToggleActive,
}: {
    program: Program;
    onEdit: (program: Program) => void;
    onToggleActive: (program: Program) => void;
}) {
    const isExecutive = program.program_modality === 'temps_amenage';

    return (
        <tr
            className={`transition-colors hover:bg-neutral-50/50 dark:hover:bg-neutral-800/40 ${
                !program.is_active ? 'bg-neutral-50/20 opacity-60' : ''
            }`}
        >
            <td className="px-6 py-4">
                <div className="font-semibold text-neutral-900 dark:text-neutral-100">
                    {program.name}
                </div>
                {program.description && (
                    <div className="line-clamp-1 text-xs text-neutral-500">
                        {program.description}
                    </div>
                )}
            </td>

            <td className="px-6 py-4">
                <span className="rounded bg-neutral-100 px-2 py-1 font-mono text-xs font-semibold dark:bg-neutral-800">
                    {program.code}
                </span>
            </td>

            <td className="px-6 py-4">
                <span className="text-sm text-neutral-600 dark:text-neutral-300">
                    {program.department?.name ?? '—'}
                </span>
            </td>

            <td className="px-6 py-4">
                {isExecutive ? (
                    <Badge
                        variant="outline"
                        className="flex w-fit items-center gap-1.5 border-amber-500/40 bg-amber-50 text-amber-800 dark:bg-amber-950/20 dark:text-amber-400"
                    >
                        <Clock className="size-3" />
                        Temps Aménagé
                    </Badge>
                ) : (
                    <Badge
                        variant="outline"
                        className="flex w-fit items-center gap-1.5 border-blue-500/40 bg-blue-50 text-blue-800 dark:bg-blue-950/20 dark:text-blue-400"
                    >
                        <Sun className="size-3" />
                        Formation Initiale
                    </Badge>
                )}
            </td>

            <td className="px-6 py-4">
                <div className="flex flex-col">
                    <span className="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                        {program.student_groups_count ?? 0} groups
                    </span>
                    <span className="flex items-center gap-1 text-xs text-neutral-500">
                        <Users className="size-3 text-neutral-400" />
                        {program.student_groups_sum_expected_headcount ??
                            0}{' '}
                        students
                    </span>
                </div>
            </td>

            <td className="px-6 py-4">
                {program.is_active ? (
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
                        onClick={() => onEdit(program)}
                        title="Edit program"
                    >
                        <Edit2 className="size-4" />
                    </Button>

                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => onToggleActive(program)}
                        title={program.is_active ? 'Deactivate' : 'Activate'}
                        className={
                            program.is_active
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

export function ProgramsTable({
    programs,
    onEdit,
    onToggleActive,
    onResetFilters,
}: ProgramsTableProps) {
    if (programs.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-neutral-300 p-12 text-center dark:border-neutral-700">
                <GraduationCap className="size-12 text-neutral-400" />
                <h3 className="mt-4 text-base font-semibold text-neutral-900 dark:text-neutral-100">
                    No programs found
                </h3>
                <p className="mt-1 text-sm text-neutral-500">
                    No academic programs match the selected filters.
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
                        <th className="px-6 py-3">Program Name</th>
                        <th className="px-6 py-3">Code</th>
                        <th className="px-6 py-3">Department</th>
                        <th className="px-6 py-3">Program Modality</th>
                        <th className="px-6 py-3">
                            Enrolled Groups & Headcount
                        </th>
                        <th className="px-6 py-3">Status</th>
                        <th className="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                    {programs.map((prog) => (
                        <ProgramRow
                            key={prog.id}
                            program={prog}
                            onEdit={onEdit}
                            onToggleActive={onToggleActive}
                        />
                    ))}
                </tbody>
            </table>
        </div>
    );
}
