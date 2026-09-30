import { useForm } from '@inertiajs/react';
import { Clock, Sun } from 'lucide-react';
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
import type { Department, Program, ProgramModality } from './types';

interface ProgramDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    departments: Department[];
    programToEdit?: Program | null;
}

export function ProgramDialog({
    open,
    onOpenChange,
    departments,
    programToEdit,
}: ProgramDialogProps) {
    const isEditing = Boolean(programToEdit);

    const form = useForm({
        department_id: departments[0]?.id?.toString() || '',
        name: '',
        code: '',
        program_modality: 'formation_initiale' as ProgramModality,
        description: '',
        is_active: true,
    });

    useEffect(() => {
        if (programToEdit) {
            form.setData({
                department_id: programToEdit.department_id.toString(),
                name: programToEdit.name,
                code: programToEdit.code,
                program_modality: programToEdit.program_modality,
                description: programToEdit.description || '',
                is_active: programToEdit.is_active,
            });
        } else {
            form.setData({
                department_id: departments[0]?.id?.toString() || '',
                name: '',
                code: '',
                program_modality: 'formation_initiale',
                description: '',
                is_active: true,
            });
        }
        form.clearErrors();
    }, [programToEdit, open]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isEditing && programToEdit) {
            form.put(`/programs/${programToEdit.id}`, {
                onSuccess: () => {
                    toast.success(
                        `Program ${form.data.name} updated successfully.`,
                    );
                    onOpenChange(false);
                },
            });
        } else {
            form.post('/programs', {
                onSuccess: () => {
                    toast.success(
                        `Program ${form.data.name} created successfully.`,
                    );
                    onOpenChange(false);
                },
            });
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[520px]">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {isEditing
                                ? 'Edit Academic Program'
                                : 'Create Academic Program'}
                        </DialogTitle>
                        <DialogDescription>
                            Configure degree programs and specify their academic
                            schedule regime (Modality).
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="prog_dept">Department *</Label>
                            <select
                                id="prog_dept"
                                value={form.data.department_id}
                                onChange={(e) =>
                                    form.setData(
                                        'department_id',
                                        e.target.value,
                                    )
                                }
                                className="w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-800 dark:bg-neutral-900"
                                required
                            >
                                <option value="" disabled>
                                    Select parent department
                                </option>
                                {departments.map((dept) => (
                                    <option key={dept.id} value={dept.id}>
                                        {dept.code} - {dept.name}
                                    </option>
                                ))}
                            </select>
                            {form.errors.department_id && (
                                <p className="text-xs text-red-500">
                                    {form.errors.department_id}
                                </p>
                            )}
                        </div>

                        <div className="grid grid-cols-3 gap-3">
                            <div className="col-span-2 grid gap-2">
                                <Label htmlFor="prog_name">
                                    Program Name *
                                </Label>
                                <Input
                                    id="prog_name"
                                    value={form.data.name}
                                    onChange={(e) =>
                                        form.setData('name', e.target.value)
                                    }
                                    placeholder="e.g. 1ère Année Cycle Ingénieur"
                                    required
                                />
                                {form.errors.name && (
                                    <p className="text-xs text-red-500">
                                        {form.errors.name}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="prog_code">Code *</Label>
                                <Input
                                    id="prog_code"
                                    value={form.data.code}
                                    onChange={(e) =>
                                        form.setData(
                                            'code',
                                            e.target.value.toUpperCase(),
                                        )
                                    }
                                    placeholder="e.g. 1CI, 2CI"
                                    required
                                />
                                {form.errors.code && (
                                    <p className="text-xs text-red-500">
                                        {form.errors.code}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label>Program Modality *</Label>
                            <div className="grid grid-cols-2 gap-3">
                                <label
                                    className={`flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors ${
                                        form.data.program_modality ===
                                        'formation_initiale'
                                            ? 'border-blue-600 bg-blue-50/40 dark:border-blue-500 dark:bg-blue-950/20'
                                            : 'border-neutral-200 hover:bg-neutral-50 dark:border-neutral-800 dark:hover:bg-neutral-800/40'
                                    }`}
                                >
                                    <input
                                        type="radio"
                                        name="program_modality"
                                        value="formation_initiale"
                                        checked={
                                            form.data.program_modality ===
                                            'formation_initiale'
                                        }
                                        onChange={() =>
                                            form.setData(
                                                'program_modality',
                                                'formation_initiale',
                                            )
                                        }
                                        className="mt-1"
                                    />
                                    <div>
                                        <div className="flex items-center gap-1.5 text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                            <Sun className="size-4 text-blue-500" />
                                            Formation Initiale
                                        </div>
                                        <div className="mt-0.5 text-xs text-neutral-500">
                                            Standard weekday daytime schedules
                                        </div>
                                    </div>
                                </label>

                                <label
                                    className={`flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors ${
                                        form.data.program_modality ===
                                        'temps_amenage'
                                            ? 'border-amber-600 bg-amber-50/40 dark:border-amber-500 dark:bg-amber-950/20'
                                            : 'border-neutral-200 hover:bg-neutral-50 dark:border-neutral-800 dark:hover:bg-neutral-800/40'
                                    }`}
                                >
                                    <input
                                        type="radio"
                                        name="program_modality"
                                        value="temps_amenage"
                                        checked={
                                            form.data.program_modality ===
                                            'temps_amenage'
                                        }
                                        onChange={() =>
                                            form.setData(
                                                'program_modality',
                                                'temps_amenage',
                                            )
                                        }
                                        className="mt-1"
                                    />
                                    <div>
                                        <div className="flex items-center gap-1.5 text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                            <Clock className="size-4 text-amber-500" />
                                            Temps Aménagé
                                        </div>
                                        <div className="mt-0.5 text-xs text-neutral-500">
                                            Executive evening & weekend slots
                                        </div>
                                    </div>
                                </label>
                            </div>
                            {form.errors.program_modality && (
                                <p className="text-xs text-red-500">
                                    {form.errors.program_modality}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="prog_desc">Description</Label>
                            <Input
                                id="prog_desc"
                                value={form.data.description}
                                onChange={(e) =>
                                    form.setData('description', e.target.value)
                                }
                                placeholder="Curriculum or syllabus summary"
                            />
                            {form.errors.description && (
                                <p className="text-xs text-red-500">
                                    {form.errors.description}
                                </p>
                            )}
                        </div>

                        <div className="flex items-center space-x-2 pt-2">
                            <Checkbox
                                id="prog_active"
                                checked={form.data.is_active}
                                onCheckedChange={(checked) =>
                                    form.setData('is_active', Boolean(checked))
                                }
                            />
                            <Label
                                htmlFor="prog_active"
                                className="cursor-pointer text-sm font-normal"
                            >
                                Active program (accepts courses and group
                                planning)
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
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing
                                ? 'Saving...'
                                : isEditing
                                  ? 'Update Program'
                                  : 'Create Program'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
