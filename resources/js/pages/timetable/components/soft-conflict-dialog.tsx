import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/i18n/LanguageContext';
import { FIELD_CLASS } from '@/lib/form-classes';
import { describeConflict } from './schedule/conflict-description';
import type { SlotConflict } from './schedule/types';
import type { TimetableLimits } from './types';

interface SoftConflictDialogProps {
    conflicts: SlotConflict[] | null;
    canOverride: boolean;
    limits: TimetableLimits;
    saving: boolean;
    onConfirm: (justification: string) => void;
    onCancel: () => void;
}

/** Asks for a justification before saving a move that breaks a soft rule (ADR 0002). */
export function SoftConflictDialog({
    conflicts,
    canOverride,
    limits,
    saving,
    onConfirm,
    onCancel,
}: SoftConflictDialogProps) {
    const { t } = useTranslation();
    const [justification, setJustification] = useState('');

    return (
        <Dialog
            open={conflicts !== null}
            onOpenChange={(open) => !open && onCancel()}
        >
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{t('timetable.soft_title')}</DialogTitle>
                    <DialogDescription>
                        {t('timetable.soft_desc')}
                    </DialogDescription>
                </DialogHeader>

                <ul className="grid gap-1 text-sm text-amber-800 dark:text-amber-300">
                    {conflicts?.map((conflict) => (
                        <li key={`${conflict.type}-${conflict.resource_id}`}>
                            {describeConflict(conflict, t)}
                        </li>
                    ))}
                </ul>

                {canOverride ? (
                    <div className="grid gap-2">
                        <Label htmlFor="reschedule_justification">
                            {t('schedule.justification_label')}
                        </Label>
                        <textarea
                            id="reschedule_justification"
                            rows={3}
                            maxLength={limits.justification_max}
                            value={justification}
                            placeholder={t(
                                'schedule.justification_placeholder',
                            )}
                            onChange={(e) => setJustification(e.target.value)}
                            className={FIELD_CLASS}
                        />
                        <p className="text-xs text-neutral-500">
                            {t('schedule.justification_hint')}
                        </p>
                    </div>
                ) : (
                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                        {t('timetable.soft_no_permission')}
                    </p>
                )}

                <DialogFooter className="gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={onCancel}
                        disabled={saving}
                    >
                        {t('timetable.soft_cancel')}
                    </Button>
                    {canOverride ? (
                        <Button
                            type="button"
                            onClick={() => onConfirm(justification.trim())}
                            disabled={
                                saving ||
                                justification.trim().length <
                                    limits.justification_min
                            }
                        >
                            {saving ? <Spinner /> : null}
                            {t('timetable.soft_confirm')}
                        </Button>
                    ) : null}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
