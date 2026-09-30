import { Clock, Edit2, MapPin, Power, Sun, Users } from 'lucide-react';
import React, { memo } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';
import type { StudentGroup } from './types';

interface StudentGroupsTableProps {
    studentGroups: StudentGroup[];
    onEdit: (group: StudentGroup) => void;
    onToggleActive: (group: StudentGroup) => void;
    onResetFilters: () => void;
}

const StudentGroupRow = memo(function StudentGroupRow({
    group,
    onEdit,
    onToggleActive,
}: {
    group: StudentGroup;
    onEdit: (group: StudentGroup) => void;
    onToggleActive: (group: StudentGroup) => void;
}) {
    const { t } = useTranslation();
    const modality = group.program?.program_modality;
    const isExecutive = modality === 'temps_amenage';

    return (
        <tr
            className={`transition-colors hover:bg-neutral-50/50 dark:hover:bg-neutral-800/40 ${
                !group.is_active ? 'bg-neutral-50/20 opacity-60' : ''
            }`}
        >
            <td className="px-6 py-4">
                <div className="font-semibold text-neutral-900 dark:text-neutral-100">
                    {group.name}
                </div>
                {group.code && (
                    <div className="font-mono text-xs text-neutral-500">
                        {group.code}
                    </div>
                )}
            </td>

            <td className="px-6 py-4">
                <div className="text-sm font-medium text-neutral-800 dark:text-neutral-200">
                    {group.program?.name ?? '—'}
                </div>
                {group.program?.department && (
                    <div className="text-xs text-neutral-400">
                        {group.program.department.name}
                    </div>
                )}
            </td>

            <td className="px-6 py-4">
                {isExecutive ? (
                    <Badge
                        variant="outline"
                        className="flex w-fit items-center gap-1 border-amber-500/40 bg-amber-50 text-amber-800 dark:bg-amber-950/20 dark:text-amber-400"
                    >
                        <Clock className="size-3" />
                        {t('academic.modality_amenage')}
                    </Badge>
                ) : (
                    <Badge
                        variant="outline"
                        className="flex w-fit items-center gap-1 border-blue-500/40 bg-blue-50 text-blue-800 dark:bg-blue-950/20 dark:text-blue-400"
                    >
                        <Sun className="size-3" />
                        {t('academic.modality_initiale')}
                    </Badge>
                )}
            </td>

            <td className="px-6 py-4">
                {group.campus ? (
                    <span className="flex items-center gap-1 text-xs text-neutral-600 dark:text-neutral-300">
                        <MapPin className="size-3 text-neutral-400" />
                        {group.campus.name}
                    </span>
                ) : (
                    <span className="text-xs text-neutral-400 italic">
                        {t('rooms.all_campuses')}
                    </span>
                )}
            </td>

            <td className="px-6 py-4 font-mono text-xs text-neutral-600 dark:text-neutral-400">
                {group.academic_year}
            </td>

            <td className="px-6 py-4">
                <div className="flex items-center gap-1.5 font-semibold text-neutral-900 dark:text-neutral-100">
                    <Users className="size-4 text-indigo-500" />
                    <span>{group.expected_headcount}</span>
                    <span className="text-xs font-normal text-neutral-400">
                        {t('academic.students_suffix')}
                    </span>
                </div>
            </td>

            <td className="px-6 py-4">
                {group.is_active ? (
                    <Badge
                        variant="outline"
                        className="border-emerald-500/30 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400"
                    >
                        {t('common.active')}
                    </Badge>
                ) : (
                    <Badge
                        variant="outline"
                        className="border-neutral-300 text-neutral-500"
                    >
                        {t('common.inactive')}
                    </Badge>
                )}
            </td>

            <td className="px-6 py-4 text-right">
                <div className="flex items-center justify-end gap-1">
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => onEdit(group)}
                        title={t('common.edit')}
                    >
                        <Edit2 className="size-4" />
                    </Button>

                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => onToggleActive(group)}
                        title={
                            group.is_active
                                ? t('common.deactivate')
                                : t('common.activate')
                        }
                        className={
                            group.is_active
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

export function StudentGroupsTable({
    studentGroups,
    onEdit,
    onToggleActive,
    onResetFilters,
}: StudentGroupsTableProps) {
    const { t } = useTranslation();

    if (studentGroups.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-neutral-300 p-12 text-center dark:border-neutral-700">
                <Users className="size-12 text-neutral-400" />
                <h3 className="mt-4 text-base font-semibold text-neutral-900 dark:text-neutral-100">
                    {t('academic.no_group_found')}
                </h3>
                <p className="mt-1 text-sm text-neutral-500">
                    {t('rooms.no_rooms_desc')}
                </p>
                <Button
                    variant="outline"
                    size="sm"
                    className="mt-4"
                    onClick={onResetFilters}
                >
                    {t('common.reset_filters')}
                </Button>
            </div>
        );
    }

    return (
        <div className="overflow-x-auto rounded-lg border border-neutral-200 bg-white shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
            <table className="w-full text-left text-sm">
                <thead className="border-b border-neutral-200 bg-neutral-50/50 text-xs font-medium text-neutral-500 dark:border-neutral-800 dark:bg-neutral-800/50 dark:text-neutral-400">
                    <tr>
                        <th className="px-6 py-3">
                            {t('academic.col_group_name')}
                        </th>
                        <th className="px-6 py-3">
                            {t('academic.col_program_dept')}
                        </th>
                        <th className="px-6 py-3">
                            {t('academic.col_modality')}
                        </th>
                        <th className="px-6 py-3">
                            {t('academic.col_campus')}
                        </th>
                        <th className="px-6 py-3">
                            {t('academic.col_academic_year')}
                        </th>
                        <th className="px-6 py-3">
                            {t('academic.col_headcount')}
                        </th>
                        <th className="px-6 py-3">{t('common.status')}</th>
                        <th className="px-6 py-3 text-right">
                            {t('common.actions')}
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                    {studentGroups.map((group) => (
                        <StudentGroupRow
                            key={group.id}
                            group={group}
                            onEdit={onEdit}
                            onToggleActive={onToggleActive}
                        />
                    ))}
                </tbody>
            </table>
        </div>
    );
}
