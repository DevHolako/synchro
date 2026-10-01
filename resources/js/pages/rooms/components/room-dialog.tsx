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
import { store as storeRoom, update as updateRoom } from '@/routes/rooms';
import type { Campus, Room } from './types';

interface RoomDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    campuses: Campus[];
    roomToEdit?: Room | null;
}

export function RoomDialog({
    open,
    onOpenChange,
    campuses,
    roomToEdit,
}: RoomDialogProps) {
    const { t } = useTranslation();
    const isEditing = Boolean(roomToEdit);

    const form = useForm({
        building_id: campuses[0]?.buildings?.[0]?.id?.toString() || '',
        name: '',
        code: '',
        floor: '0',
        course_capacity: '40',
        exam_capacity: '20',
        has_projector: false,
        is_lab: false,
        has_computers: false,
        has_sound_system: false,
        is_active: true,
    });

    useEffect(() => {
        if (roomToEdit) {
            form.setData({
                building_id: roomToEdit.building_id.toString(),
                name: roomToEdit.name,
                code: roomToEdit.code || '',
                floor: roomToEdit.floor?.toString() || '0',
                course_capacity: roomToEdit.course_capacity.toString(),
                exam_capacity: roomToEdit.exam_capacity.toString(),
                has_projector: roomToEdit.has_projector,
                is_lab: roomToEdit.is_lab,
                has_computers: roomToEdit.has_computers,
                has_sound_system: roomToEdit.has_sound_system,
                is_active: roomToEdit.is_active,
            });
        } else {
            form.setData({
                building_id: campuses[0]?.buildings?.[0]?.id?.toString() || '',
                name: '',
                code: '',
                floor: '0',
                course_capacity: '40',
                exam_capacity: '20',
                has_projector: false,
                is_lab: false,
                has_computers: false,
                has_sound_system: false,
                is_active: true,
            });
        }
    }, [roomToEdit, open]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const course = parseInt(form.data.course_capacity, 10);
        const exam = parseInt(form.data.exam_capacity, 10);

        if (exam > course) {
            toast.error(t('toasts.error_exam_capacity_exceeds'));
            return;
        }

        if (isEditing && roomToEdit) {
            form.put(updateRoom.url(roomToEdit.id), {
                onSuccess: () => {
                    onOpenChange(false);
                },
                onError: (errors) => {
                    const firstError = Object.values(errors)[0];
                    if (firstError) toast.error(firstError as string);
                },
            });
        } else {
            form.post(storeRoom.url(), {
                onSuccess: () => {
                    onOpenChange(false);
                    form.reset();
                },
                onError: (errors) => {
                    const firstError = Object.values(errors)[0];
                    if (firstError) toast.error(firstError as string);
                },
            });
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {isEditing
                                ? t('rooms.dialog_edit_title', {
                                      name: roomToEdit?.name || '',
                                  })
                                : t('rooms.dialog_create_title')}
                        </DialogTitle>
                        <DialogDescription>
                            {t('rooms.dialog_description')}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="room_dialog_building_id">
                                {t('rooms.dialog_building')}
                            </Label>
                            <select
                                id="room_dialog_building_id"
                                value={form.data.building_id}
                                onChange={(e) =>
                                    form.setData('building_id', e.target.value)
                                }
                                className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs"
                                required
                            >
                                {campuses.map((c) => (
                                    <optgroup
                                        key={c.id}
                                        label={`${c.name} (${c.code})`}
                                    >
                                        {c.buildings?.map((b) => (
                                            <option key={b.id} value={b.id}>
                                                {b.name}
                                            </option>
                                        ))}
                                    </optgroup>
                                ))}
                            </select>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="room_dialog_name">
                                    {t('rooms.dialog_name')}
                                </Label>
                                <Input
                                    id="room_dialog_name"
                                    placeholder={t(
                                        'rooms.dialog_name_placeholder',
                                    )}
                                    value={form.data.name}
                                    onChange={(e) =>
                                        form.setData('name', e.target.value)
                                    }
                                    required
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="room_dialog_code">
                                    {t('rooms.dialog_code')}
                                </Label>
                                <Input
                                    id="room_dialog_code"
                                    placeholder={t(
                                        'rooms.dialog_code_placeholder',
                                    )}
                                    value={form.data.code}
                                    onChange={(e) =>
                                        form.setData('code', e.target.value)
                                    }
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-3 gap-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="room_dialog_floor">
                                    {t('rooms.dialog_floor')}
                                </Label>
                                <Input
                                    id="room_dialog_floor"
                                    type="number"
                                    value={form.data.floor}
                                    onChange={(e) =>
                                        form.setData('floor', e.target.value)
                                    }
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="room_dialog_course_capacity">
                                    {t('rooms.dialog_course_cap')}
                                </Label>
                                <Input
                                    id="room_dialog_course_capacity"
                                    type="number"
                                    min="1"
                                    value={form.data.course_capacity}
                                    onChange={(e) => {
                                        const course = e.target.value;
                                        const examVal = Math.floor(
                                            parseInt(course || '0', 10) / 2,
                                        );
                                        form.setData({
                                            ...form.data,
                                            course_capacity: course,
                                            exam_capacity:
                                                examVal > 0
                                                    ? examVal.toString()
                                                    : '1',
                                        });
                                    }}
                                    required
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="room_dialog_exam_capacity">
                                    {t('rooms.dialog_exam_cap')}
                                </Label>
                                <Input
                                    id="room_dialog_exam_capacity"
                                    type="number"
                                    min="1"
                                    max={form.data.course_capacity}
                                    value={form.data.exam_capacity}
                                    onChange={(e) =>
                                        form.setData(
                                            'exam_capacity',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                            </div>
                        </div>

                        <div className="rounded-lg bg-neutral-50 p-2.5 text-xs text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400">
                            💡{' '}
                            <strong>{t('rooms.dialog_capacity_rule')}</strong>{' '}
                            {t('rooms.dialog_capacity_rule_text')}
                        </div>

                        <div className="space-y-2 pt-2">
                            <Label className="text-xs font-semibold text-neutral-500 uppercase">
                                {t('rooms.dialog_equipment_section')}
                            </Label>
                            <div className="grid grid-cols-2 gap-2">
                                <label className="flex cursor-pointer items-center gap-2 text-xs">
                                    <Checkbox
                                        checked={form.data.has_projector}
                                        onCheckedChange={(c) =>
                                            form.setData('has_projector', !!c)
                                        }
                                    />
                                    {t('rooms.projector')}
                                </label>
                                <label className="flex cursor-pointer items-center gap-2 text-xs">
                                    <Checkbox
                                        checked={form.data.is_lab}
                                        onCheckedChange={(c) =>
                                            form.setData('is_lab', !!c)
                                        }
                                    />
                                    {t('rooms.lab')}
                                </label>
                                <label className="flex cursor-pointer items-center gap-2 text-xs">
                                    <Checkbox
                                        checked={form.data.has_computers}
                                        onCheckedChange={(c) =>
                                            form.setData('has_computers', !!c)
                                        }
                                    />
                                    {t('rooms.computers')}
                                </label>
                                <label className="flex cursor-pointer items-center gap-2 text-xs">
                                    <Checkbox
                                        checked={form.data.has_sound_system}
                                        onCheckedChange={(c) =>
                                            form.setData(
                                                'has_sound_system',
                                                !!c,
                                            )
                                        }
                                    />
                                    {t('rooms.sound')}
                                </label>
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
                                : isEditing
                                  ? t('common.save')
                                  : t('rooms.new_room')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
