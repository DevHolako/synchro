import { useForm } from '@inertiajs/react';
import React, { useEffect } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
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
import type { Campus } from './types';

interface BuildingDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    campuses: Campus[];
}

export function BuildingDialog({
    open,
    onOpenChange,
    campuses,
}: BuildingDialogProps) {
    const { t } = useTranslation();

    const form = useForm({
        campus_id: campuses[0]?.id?.toString() || '',
        name: '',
        code: '',
    });

    useEffect(() => {
        if (!form.data.campus_id && campuses[0]) {
            form.setData('campus_id', campuses[0].id.toString());
        }
    }, [campuses]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/buildings', {
            onSuccess: () => {
                onOpenChange(false);
                form.reset();
                toast.success(t('toasts.building_created'));
            },
            onError: (errors) => {
                const first = Object.values(errors)[0];
                if (first) toast.error(first as string);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {t('rooms.dialog_building_title')}
                        </DialogTitle>
                        <DialogDescription>
                            {t('rooms.dialog_building_desc')}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-3 py-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="dialog_building_campus_id">
                                {t('academic.col_campus')} *
                            </Label>
                            <select
                                id="dialog_building_campus_id"
                                value={form.data.campus_id}
                                onChange={(e) =>
                                    form.setData('campus_id', e.target.value)
                                }
                                className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs"
                                required
                            >
                                {campuses.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.name} ({c.code})
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="dialog_building_name">
                                    {t('rooms.dialog_building_name')}
                                </Label>
                                <Input
                                    id="dialog_building_name"
                                    placeholder={t(
                                        'rooms.dialog_building_name_placeholder',
                                    )}
                                    value={form.data.name}
                                    onChange={(e) =>
                                        form.setData('name', e.target.value)
                                    }
                                    required
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="dialog_building_code">
                                    {t('rooms.dialog_building_code')}
                                </Label>
                                <Input
                                    id="dialog_building_code"
                                    placeholder={t(
                                        'rooms.dialog_building_code_placeholder',
                                    )}
                                    value={form.data.code}
                                    onChange={(e) =>
                                        form.setData(
                                            'code',
                                            e.target.value.toUpperCase(),
                                        )
                                    }
                                />
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => onOpenChange(false)}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing
                                ? t('common.saving')
                                : t('rooms.dialog_building_title')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
