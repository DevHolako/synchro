import { useForm } from '@inertiajs/react';
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
import type { Department } from './types';

interface DepartmentDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    departmentToEdit?: Department | null;
}

export function DepartmentDialog({
    open,
    onOpenChange,
    departmentToEdit,
}: DepartmentDialogProps) {
    const isEditing = Boolean(departmentToEdit);

    const form = useForm({
        name: '',
        code: '',
        description: '',
        is_active: true,
    });

    useEffect(() => {
        if (departmentToEdit) {
            form.setData({
                name: departmentToEdit.name,
                code: departmentToEdit.code,
                description: departmentToEdit.description || '',
                is_active: departmentToEdit.is_active,
            });
        } else {
            form.setData({
                name: '',
                code: '',
                description: '',
                is_active: true,
            });
        }
        form.clearErrors();
    }, [departmentToEdit, open]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isEditing && departmentToEdit) {
            form.put(`/departments/${departmentToEdit.id}`, {
                onSuccess: () => {
                    toast.success(
                        `Department ${form.data.name} updated successfully.`,
                    );
                    onOpenChange(false);
                },
            });
        } else {
            form.post('/departments', {
                onSuccess: () => {
                    toast.success(
                        `Department ${form.data.name} created successfully.`,
                    );
                    onOpenChange(false);
                },
            });
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[480px]">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {isEditing
                                ? 'Edit Academic Department'
                                : 'Create Academic Department'}
                        </DialogTitle>
                        <DialogDescription>
                            Organize faculties, programs, and student cohorts
                            within a central department.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="dept_name">Department Name *</Label>
                            <Input
                                id="dept_name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                placeholder="e.g. Informatique & Systèmes d'Information"
                                required
                            />
                            {form.errors.name && (
                                <p className="text-xs text-red-500">
                                    {form.errors.name}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="dept_code">Department Code *</Label>
                            <Input
                                id="dept_code"
                                value={form.data.code}
                                onChange={(e) =>
                                    form.setData(
                                        'code',
                                        e.target.value.toUpperCase(),
                                    )
                                }
                                placeholder="e.g. ISI, MGT"
                                required
                            />
                            {form.errors.code && (
                                <p className="text-xs text-red-500">
                                    {form.errors.code}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="dept_desc">Description</Label>
                            <Input
                                id="dept_desc"
                                value={form.data.description}
                                onChange={(e) =>
                                    form.setData('description', e.target.value)
                                }
                                placeholder="Brief overview of department scope"
                            />
                            {form.errors.description && (
                                <p className="text-xs text-red-500">
                                    {form.errors.description}
                                </p>
                            )}
                        </div>

                        <div className="flex items-center space-x-2 pt-2">
                            <Checkbox
                                id="dept_active"
                                checked={form.data.is_active}
                                onCheckedChange={(checked) =>
                                    form.setData('is_active', Boolean(checked))
                                }
                            />
                            <Label
                                htmlFor="dept_active"
                                className="cursor-pointer text-sm font-normal"
                            >
                                Active department (visible in timetable
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
                                  ? 'Update Department'
                                  : 'Create Department'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
