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
import { useTranslation } from '@/i18n/LanguageContext';
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
    const { t } = useTranslation();
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
                    toast.success(t('toasts.department_updated'));
                    onOpenChange(false);
                },
            });
        } else {
            form.post('/departments', {
                onSuccess: () => {
                    toast.success(t('toasts.department_created'));
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
                                ? t('academic.dept_dialog_edit_title', {
                                      name: departmentToEdit?.name ?? '',
                                  })
                                : t('academic.dept_dialog_create_title')}
                        </DialogTitle>
                        <DialogDescription>
                            {t('academic.dept_dialog_desc')}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="dept_name">
                                {t('academic.dept_name')}
                            </Label>
                            <Input
                                id="dept_name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                placeholder={t(
                                    'academic.dept_name_placeholder',
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
                            <Label htmlFor="dept_code">
                                {t('academic.dept_code')}
                            </Label>
                            <Input
                                id="dept_code"
                                value={form.data.code}
                                onChange={(e) =>
                                    form.setData(
                                        'code',
                                        e.target.value.toUpperCase(),
                                    )
                                }
                                placeholder={t(
                                    'academic.dept_code_placeholder',
                                )}
                                required
                            />
                            {form.errors.code && (
                                <p className="text-xs text-red-500">
                                    {form.errors.code}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="dept_desc">
                                {t('common.description')}
                            </Label>
                            <Input
                                id="dept_desc"
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
                                {t('academic.dept_active_label')}
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
                        <Button type="submit" disabled={form.processing}>
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
