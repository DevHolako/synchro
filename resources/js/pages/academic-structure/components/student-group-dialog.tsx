import { useForm } from '@inertiajs/react';
import { Users } from 'lucide-react';
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
import type { Campus, Program, StudentGroup } from './types';

interface StudentGroupDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    programs: Program[];
    campuses: Campus[];
    groupToEdit?: StudentGroup | null;
}

export function StudentGroupDialog({
    open,
    onOpenChange,
    programs,
    campuses,
    groupToEdit,
}: StudentGroupDialogProps) {
    const isEditing = Boolean(groupToEdit);

    const form = useForm({
        program_id: programs[0]?.id?.toString() || '',
        campus_id: '',
        name: '',
        code: '',
        academic_year: '2026-2027',
        expected_headcount: '30',
        is_active: true,
    });

    useEffect(() => {
        if (groupToEdit) {
            form.setData({
                program_id: groupToEdit.program_id.toString(),
                campus_id: groupToEdit.campus_id
                    ? groupToEdit.campus_id.toString()
                    : '',
                name: groupToEdit.name,
                code: groupToEdit.code || '',
                academic_year: groupToEdit.academic_year,
                expected_headcount: groupToEdit.expected_headcount.toString(),
                is_active: groupToEdit.is_active,
            });
        } else {
            form.setData({
                program_id: programs[0]?.id?.toString() || '',
                campus_id: '',
                name: '',
                code: '',
                academic_year: '2026-2027',
                expected_headcount: '30',
                is_active: true,
            });
        }
        form.clearErrors();
    }, [groupToEdit, open]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const count = parseInt(form.data.expected_headcount, 10);
        if (isNaN(count) || count <= 0) {
            toast.error('Expected headcount must be greater than 0.');
            return;
        }

        if (isEditing && groupToEdit) {
            form.put(`/student-groups/${groupToEdit.id}`, {
                onSuccess: () => {
                    toast.success(
                        `Student group ${form.data.name} updated successfully.`,
                    );
                    onOpenChange(false);
                },
            });
        } else {
            form.post('/student-groups', {
                onSuccess: () => {
                    toast.success(
                        `Student group ${form.data.name} created successfully.`,
                    );
                    onOpenChange(false);
                },
            });
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[500px]">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {isEditing
                                ? 'Edit Student Group'
                                : 'Create Student Group'}
                        </DialogTitle>
                        <DialogDescription>
                            Define a student cohort with designated enrollment
                            headcount for room capacity checks.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="grp_prog">Program *</Label>
                            <select
                                id="grp_prog"
                                value={form.data.program_id}
                                onChange={(e) =>
                                    form.setData('program_id', e.target.value)
                                }
                                className="w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-800 dark:bg-neutral-900"
                                required
                            >
                                <option value="" disabled>
                                    Select parent program
                                </option>
                                {programs.map((prog) => (
                                    <option key={prog.id} value={prog.id}>
                                        {prog.code} - {prog.name} (
                                        {prog.program_modality ===
                                        'temps_amenage'
                                            ? 'Temps Aménagé'
                                            : 'Formation Initiale'}
                                        )
                                    </option>
                                ))}
                            </select>
                            {form.errors.program_id && (
                                <p className="text-xs text-red-500">
                                    {form.errors.program_id}
                                </p>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="grp_campus">
                                    Campus (Optional)
                                </Label>
                                <select
                                    id="grp_campus"
                                    value={form.data.campus_id}
                                    onChange={(e) =>
                                        form.setData(
                                            'campus_id',
                                            e.target.value,
                                        )
                                    }
                                    className="w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-800 dark:bg-neutral-900"
                                >
                                    <option value="">Institution-wide</option>
                                    {campuses.map((campus) => (
                                        <option
                                            key={campus.id}
                                            value={campus.id}
                                        >
                                            {campus.name}
                                        </option>
                                    ))}
                                </select>
                                {form.errors.campus_id && (
                                    <p className="text-xs text-red-500">
                                        {form.errors.campus_id}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="grp_year">
                                    Academic Year *
                                </Label>
                                <Input
                                    id="grp_year"
                                    value={form.data.academic_year}
                                    onChange={(e) =>
                                        form.setData(
                                            'academic_year',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="2026-2027"
                                    required
                                />
                                {form.errors.academic_year && (
                                    <p className="text-xs text-red-500">
                                        {form.errors.academic_year}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-3 gap-3">
                            <div className="col-span-2 grid gap-2">
                                <Label htmlFor="grp_name">Group Name *</Label>
                                <Input
                                    id="grp_name"
                                    value={form.data.name}
                                    onChange={(e) =>
                                        form.setData('name', e.target.value)
                                    }
                                    placeholder="e.g. 1CI - Groupe A"
                                    required
                                />
                                {form.errors.name && (
                                    <p className="text-xs text-red-500">
                                        {form.errors.name}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="grp_code">Code</Label>
                                <Input
                                    id="grp_code"
                                    value={form.data.code}
                                    onChange={(e) =>
                                        form.setData(
                                            'code',
                                            e.target.value.toUpperCase(),
                                        )
                                    }
                                    placeholder="e.g. G1"
                                />
                                {form.errors.code && (
                                    <p className="text-xs text-red-500">
                                        {form.errors.code}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label
                                htmlFor="grp_headcount"
                                className="flex items-center gap-1.5"
                            >
                                <Users className="size-4 text-indigo-500" />
                                Expected Student Headcount *
                            </Label>
                            <Input
                                id="grp_headcount"
                                type="number"
                                min="1"
                                value={form.data.expected_headcount}
                                onChange={(e) =>
                                    form.setData(
                                        'expected_headcount',
                                        e.target.value,
                                    )
                                }
                                required
                            />
                            <p className="text-xs text-neutral-500">
                                Strictly enforced against room Course Capacity
                                during timetable scheduling.
                            </p>
                            {form.errors.expected_headcount && (
                                <p className="text-xs text-red-500">
                                    {form.errors.expected_headcount}
                                </p>
                            )}
                        </div>

                        <div className="flex items-center space-x-2 pt-2">
                            <Checkbox
                                id="grp_active"
                                checked={form.data.is_active}
                                onCheckedChange={(checked) =>
                                    form.setData('is_active', Boolean(checked))
                                }
                            />
                            <Label
                                htmlFor="grp_active"
                                className="cursor-pointer text-sm font-normal"
                            >
                                Active group (enrolled in current timetable
                                sessions)
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
                                  ? 'Update Group'
                                  : 'Create Group'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
