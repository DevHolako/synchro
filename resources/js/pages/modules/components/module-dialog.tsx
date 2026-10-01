import { useForm } from '@inertiajs/react';
import { AlertCircle, Palette } from 'lucide-react';
import React, { useEffect } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/i18n/LanguageContext';
import { store as storeModule, update as updateModule } from '@/routes/modules';
import type { Module, Program, Teacher } from './types';

const COLOR_PRESETS = [
    { label: 'Blue', hex: '#3B82F6' },
    { label: 'Emerald', hex: '#10B981' },
    { label: 'Purple', hex: '#8B5CF6' },
    { label: 'Pink', hex: '#EC4899' },
    { label: 'Amber', hex: '#F59E0B' },
    { label: 'Indigo', hex: '#6366F1' },
    { label: 'Cyan', hex: '#06B6D4' },
    { label: 'Teal', hex: '#14B8A6' },
    { label: 'Red', hex: '#EF4444' },
    { label: 'Lime', hex: '#84CC16' },
];

interface ModuleDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    programs: Program[];
    teachers: Teacher[];
    moduleToEdit?: Module | null;
}

export function ModuleDialog({
    open,
    onOpenChange,
    programs,
    teachers,
    moduleToEdit,
}: ModuleDialogProps) {
    const { t } = useTranslation();
    const isEditing = Boolean(moduleToEdit);

    const form = useForm({
        program_id: programs[0]?.id?.toString() || '',
        teacher_id: '',
        name: '',
        code: '',
        total_hours: '40',
        lecture_hours: '24',
        tp_hours: '16',
        color_code: '#3B82F6',
        description: '',
        is_active: true,
    });

    useEffect(() => {
        if (moduleToEdit) {
            form.setData({
                program_id: moduleToEdit.program_id.toString(),
                teacher_id: moduleToEdit.teacher_id
                    ? moduleToEdit.teacher_id.toString()
                    : '',
                name: moduleToEdit.name,
                code: moduleToEdit.code,
                total_hours: moduleToEdit.total_hours.toString(),
                lecture_hours: moduleToEdit.lecture_hours.toString(),
                tp_hours: moduleToEdit.tp_hours.toString(),
                color_code: moduleToEdit.color_code,
                description: moduleToEdit.description || '',
                is_active: moduleToEdit.is_active,
            });
        } else {
            form.setData({
                program_id: programs[0]?.id?.toString() || '',
                teacher_id: '',
                name: '',
                code: '',
                total_hours: '40',
                lecture_hours: '24',
                tp_hours: '16',
                color_code: '#3B82F6',
                description: '',
                is_active: true,
            });
        }
        form.clearErrors();
    }, [moduleToEdit, open]);

    const totalHours = parseInt(form.data.total_hours, 10) || 0;
    const lectureHours = parseInt(form.data.lecture_hours, 10) || 0;
    const tpHours = parseInt(form.data.tp_hours, 10) || 0;
    const sumHours = lectureHours + tpHours;
    const isHoursExceeded = sumHours > totalHours;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (totalHours <= 0) {
            toast.error(t('toasts.error_total_hours_positive'));
            return;
        }

        if (isHoursExceeded) {
            toast.error(t('toasts.error_hours_exceed_total'));
            return;
        }

        if (isEditing && moduleToEdit) {
            form.put(updateModule.url(moduleToEdit.id), {
                onSuccess: () => {
                    onOpenChange(false);
                },
            });
        } else {
            form.post(storeModule.url(), {
                onSuccess: () => {
                    onOpenChange(false);
                },
            });
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[560px]">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {isEditing
                                ? t('modules.dialog_edit_title', {
                                      name: moduleToEdit?.name ?? '',
                                  })
                                : t('modules.dialog_create_title')}
                        </DialogTitle>
                        <DialogDescription>
                            {t('modules.dialog_desc')}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="mod_prog">
                                    {t('modules.dialog_program')}
                                </Label>
                                <select
                                    id="mod_prog"
                                    value={form.data.program_id}
                                    onChange={(e) =>
                                        form.setData(
                                            'program_id',
                                            e.target.value,
                                        )
                                    }
                                    className="w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-800 dark:bg-neutral-900"
                                    required
                                >
                                    <option value="" disabled>
                                        {t('modules.dialog_program_select')}
                                    </option>
                                    {programs.map((p) => (
                                        <option key={p.id} value={p.id}>
                                            {p.code} - {p.name}
                                        </option>
                                    ))}
                                </select>
                                {form.errors.program_id && (
                                    <p className="text-xs text-red-500">
                                        {form.errors.program_id}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="mod_teacher">
                                    {t('modules.dialog_teacher')}
                                </Label>
                                <select
                                    id="mod_teacher"
                                    value={form.data.teacher_id}
                                    onChange={(e) =>
                                        form.setData(
                                            'teacher_id',
                                            e.target.value,
                                        )
                                    }
                                    className="w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-800 dark:bg-neutral-900"
                                >
                                    <option value="">
                                        {t('modules.dialog_no_teacher')}
                                    </option>
                                    {teachers.map((t) => (
                                        <option key={t.id} value={t.id}>
                                            {t.name}
                                        </option>
                                    ))}
                                </select>
                                {form.errors.teacher_id && (
                                    <p className="text-xs text-red-500">
                                        {form.errors.teacher_id}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-3 gap-3">
                            <div className="col-span-2 grid gap-2">
                                <Label htmlFor="mod_name">
                                    {t('modules.dialog_name')}
                                </Label>
                                <Input
                                    id="mod_name"
                                    value={form.data.name}
                                    onChange={(e) =>
                                        form.setData('name', e.target.value)
                                    }
                                    placeholder={t(
                                        'modules.dialog_name_placeholder',
                                    )}
                                    required
                                />
                                {form.errors.name && (
                                    <p className="text-xs text-red-500">
                                        {form.errors.name}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="mod_code">
                                    {t('modules.dialog_code')}
                                </Label>
                                <Input
                                    id="mod_code"
                                    value={form.data.code}
                                    onChange={(e) =>
                                        form.setData(
                                            'code',
                                            e.target.value.toUpperCase(),
                                        )
                                    }
                                    placeholder={t(
                                        'modules.dialog_code_placeholder',
                                    )}
                                    required
                                />
                                {form.errors.code && (
                                    <p className="text-xs text-red-500">
                                        {form.errors.code}
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Syllabus Hours Section */}
                        <div className="rounded-lg border border-neutral-200 bg-neutral-50/50 p-3 dark:border-neutral-800 dark:bg-neutral-800/40">
                            <div className="mb-2 text-xs font-semibold text-neutral-800 dark:text-neutral-200">
                                {t('modules.dialog_syllabus_section')}
                            </div>

                            <div className="grid grid-cols-3 gap-3">
                                <div className="grid gap-1">
                                    <Label
                                        htmlFor="mod_tot"
                                        className="text-xs"
                                    >
                                        {t('modules.dialog_total_hours')}
                                    </Label>
                                    <Input
                                        id="mod_tot"
                                        type="number"
                                        min="1"
                                        value={form.data.total_hours}
                                        onChange={(e) =>
                                            form.setData(
                                                'total_hours',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                </div>

                                <div className="grid gap-1">
                                    <Label
                                        htmlFor="mod_lec"
                                        className="text-xs"
                                    >
                                        {t('modules.dialog_lecture_hours')}
                                    </Label>
                                    <Input
                                        id="mod_lec"
                                        type="number"
                                        min="0"
                                        value={form.data.lecture_hours}
                                        onChange={(e) =>
                                            form.setData(
                                                'lecture_hours',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                </div>

                                <div className="grid gap-1">
                                    <Label htmlFor="mod_tp" className="text-xs">
                                        {t('modules.dialog_tp_hours')}
                                    </Label>
                                    <Input
                                        id="mod_tp"
                                        type="number"
                                        min="0"
                                        value={form.data.tp_hours}
                                        onChange={(e) =>
                                            form.setData(
                                                'tp_hours',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                </div>
                            </div>

                            {isHoursExceeded && (
                                <div className="mt-2 flex items-center gap-1.5 text-xs font-medium text-red-600">
                                    <AlertCircle className="size-3.5" />
                                    {t('modules.dialog_hours_warning', {
                                        lecture: lectureHours,
                                        tp: tpHours,
                                        sum: sumHours,
                                        total: totalHours,
                                    })}
                                </div>
                            )}

                            {form.errors.lecture_hours && (
                                <p className="mt-1 text-xs text-red-500">
                                    {form.errors.lecture_hours}
                                </p>
                            )}
                        </div>

                        {/* Color Code Section */}
                        <div className="grid gap-2">
                            <Label className="flex items-center gap-1.5">
                                <Palette className="size-4 text-neutral-500" />
                                {t('modules.dialog_color_section')}
                            </Label>

                            <div className="flex items-center gap-2">
                                <div className="flex flex-1 flex-wrap gap-1.5">
                                    {COLOR_PRESETS.map((preset) => (
                                        <button
                                            key={preset.hex}
                                            type="button"
                                            onClick={() =>
                                                form.setData(
                                                    'color_code',
                                                    preset.hex,
                                                )
                                            }
                                            style={{
                                                backgroundColor: preset.hex,
                                            }}
                                            className={`size-6 rounded-full transition-transform hover:scale-110 ${
                                                form.data.color_code.toLowerCase() ===
                                                preset.hex.toLowerCase()
                                                    ? 'ring-2 ring-neutral-900 ring-offset-2 dark:ring-white'
                                                    : ''
                                            }`}
                                            title={preset.label}
                                        />
                                    ))}
                                </div>

                                <Input
                                    value={form.data.color_code}
                                    onChange={(e) =>
                                        form.setData(
                                            'color_code',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="#3B82F6"
                                    className="w-[100px] font-mono text-xs uppercase"
                                    required
                                />

                                {/* Real-time Preview Pill */}
                                <div
                                    className="flex h-9 items-center justify-center rounded-md px-3 font-mono text-xs font-bold text-white shadow-xs"
                                    style={{
                                        backgroundColor: form.data.color_code,
                                    }}
                                    title={t('modules.dialog_preview_badge')}
                                >
                                    {form.data.code || 'CODE'}
                                </div>
                            </div>
                            {form.errors.color_code && (
                                <p className="text-xs text-red-500">
                                    {form.errors.color_code}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="mod_desc">
                                {t('common.description')}
                            </Label>
                            <Input
                                id="mod_desc"
                                value={form.data.description}
                                onChange={(e) =>
                                    form.setData('description', e.target.value)
                                }
                                placeholder={t(
                                    'common.description_placeholder',
                                )}
                            />
                            {form.errors.description && (
                                <p className="text-xs text-red-500">
                                    {form.errors.description}
                                </p>
                            )}
                        </div>

                        <div className="flex items-center space-x-2 pt-1">
                            <Checkbox
                                id="mod_active"
                                checked={form.data.is_active}
                                onCheckedChange={(checked) =>
                                    form.setData('is_active', Boolean(checked))
                                }
                            />
                            <Label
                                htmlFor="mod_active"
                                className="cursor-pointer text-sm font-normal"
                            >
                                {t('modules.dialog_active_label')}
                            </Label>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                            disabled={form.processing}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button
                            type="submit"
                            disabled={form.processing || isHoursExceeded}
                        >
                            {form.processing
                                ? t('common.saving')
                                : isEditing
                                  ? t('common.edit')
                                  : t('common.create')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
