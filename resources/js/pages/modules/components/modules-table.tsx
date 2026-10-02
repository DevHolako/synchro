import { BookOpen, Edit2, GraduationCap, Power } from 'lucide-react';
import React, { memo } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';
import type { Module } from './types';

interface ModulesTableProps {
    modules: Module[];
    onEdit: (module: Module) => void;
    onToggleActive: (module: Module) => void;
    onResetFilters: () => void;
}

const ModuleRow = memo(function ModuleRow({
    module,
    onEdit,
    onToggleActive,
}: {
    module: Module;
    onEdit: (module: Module) => void;
    onToggleActive: (module: Module) => void;
}) {
    const { t } = useTranslation();
    const lecturePct =
        module.total_hours > 0
            ? (module.lecture_hours / module.total_hours) * 100
            : 0;
    const tpPct =
        module.total_hours > 0
            ? (module.tp_hours / module.total_hours) * 100
            : 0;

    return (
        <tr
            className={`transition-colors hover:bg-neutral-50/50 dark:hover:bg-neutral-800/40 ${
                !module.is_active ? 'bg-neutral-50/20 opacity-60' : ''
            }`}
        >
            <td className="px-6 py-4">
                <div className="flex items-center gap-3">
                    {/* Calendar Badge Preview */}
                    <div
                        className="flex h-9 items-center justify-center rounded-md px-2.5 font-mono text-xs font-bold text-white shadow-xs"
                        style={{ backgroundColor: module.color_code }}
                        title={t('modules.badge_preview', {
                            code: module.color_code,
                        })}
                    >
                        {module.code}
                    </div>

                    <div>
                        <div className="font-semibold text-neutral-900 dark:text-neutral-100">
                            {module.name}
                        </div>
                        {module.description && (
                            <div className="line-clamp-1 max-w-[320px] text-xs text-neutral-500">
                                {module.description}
                            </div>
                        )}
                    </div>
                </div>
            </td>

            <td className="px-6 py-4">
                <div className="text-sm font-medium text-neutral-800 dark:text-neutral-200">
                    {module.program?.name ?? '—'}
                </div>
                {module.program?.department && (
                    <div className="text-xs text-neutral-400">
                        {module.program.department.name}
                    </div>
                )}
            </td>

            <td className="px-6 py-4">
                <div className="flex w-[160px] flex-col gap-1.5">
                    <div className="flex items-center justify-between text-xs">
                        <span className="font-bold text-neutral-900 dark:text-neutral-100">
                            {t('modules.hours_total', {
                                hours: module.total_hours,
                            })}
                        </span>
                        <span className="text-neutral-500">
                            {t('modules.hours_split', {
                                lecture: module.lecture_hours,
                                tp: module.tp_hours,
                            })}
                        </span>
                    </div>

                    {/* Progress bar visualizing Lecture vs TP vs Other */}
                    <div className="flex h-2 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                        <div
                            style={{ width: `${lecturePct}%` }}
                            className="bg-blue-500"
                            title={`${t('modules.dialog_lecture_hours')}: ${module.lecture_hours}h (${Math.round(lecturePct)}%)`}
                        />
                        <div
                            style={{ width: `${tpPct}%` }}
                            className="bg-emerald-500"
                            title={`${t('modules.dialog_tp_hours')}: ${module.tp_hours}h (${Math.round(tpPct)}%)`}
                        />
                    </div>

                    <span className="text-xs text-neutral-500">
                        {t('modules.weighting_split', {
                            cc: module.continuous_assessment_weight,
                            exam: module.exam_weight,
                        })}
                    </span>
                </div>
            </td>

            <td className="px-6 py-4">
                {module.teacher ? (
                    <div className="flex items-center gap-1.5">
                        <GraduationCap className="size-4 text-indigo-500" />
                        <div>
                            <div className="text-xs font-medium text-neutral-900 dark:text-neutral-100">
                                {module.teacher.name}
                            </div>
                            <div className="font-mono text-[11px] text-neutral-400">
                                {module.teacher.email}
                            </div>
                        </div>
                    </div>
                ) : (
                    <span className="text-xs text-neutral-400 italic">
                        {t('modules.unassigned')}
                    </span>
                )}
            </td>

            <td className="px-6 py-4">
                {module.is_active ? (
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
                        onClick={() => onEdit(module)}
                        title={t('common.edit')}
                    >
                        <Edit2 className="size-4" />
                    </Button>

                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => onToggleActive(module)}
                        title={
                            module.is_active
                                ? t('common.deactivate')
                                : t('common.activate')
                        }
                        className={
                            module.is_active
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

export function ModulesTable({
    modules,
    onEdit,
    onToggleActive,
    onResetFilters,
}: ModulesTableProps) {
    const { t } = useTranslation();

    if (modules.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-neutral-300 p-12 text-center dark:border-neutral-700">
                <BookOpen className="size-12 text-neutral-400" />
                <h3 className="mt-4 text-base font-semibold text-neutral-900 dark:text-neutral-100">
                    {t('modules.no_modules_found')}
                </h3>
                <p className="mt-1 text-sm text-neutral-500">
                    {t('modules.no_modules_desc')}
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
                        <th className="px-6 py-3">{t('modules.col_module')}</th>
                        <th className="px-6 py-3">
                            {t('modules.col_program')}
                        </th>
                        <th className="px-6 py-3">
                            {t('modules.col_syllabus')}
                        </th>
                        <th className="px-6 py-3">
                            {t('modules.col_instructor')}
                        </th>
                        <th className="px-6 py-3">{t('common.status')}</th>
                        <th className="px-6 py-3 text-right">
                            {t('common.actions')}
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                    {modules.map((module) => (
                        <ModuleRow
                            key={module.id}
                            module={module}
                            onEdit={onEdit}
                            onToggleActive={onToggleActive}
                        />
                    ))}
                </tbody>
            </table>
        </div>
    );
}
