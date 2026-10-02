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

/** Mirrors `ReturnGradeSheetRequest`. */
const REASON_MIN = 10;
const REASON_MAX = 1000;

interface GradeReturnDialogProps {
    open: boolean;
    processing: boolean;
    onOpenChange: (open: boolean) => void;
    onConfirm: (reason: string) => void;
}

export function GradeReturnDialog({
    open,
    processing,
    onOpenChange,
    onConfirm,
}: GradeReturnDialogProps) {
    const { t } = useTranslation();
    const [reason, setReason] = useState('');
    const valid = reason.trim().length >= REASON_MIN;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('grades.return_title')}</DialogTitle>
                    <DialogDescription>
                        {t('grades.return_desc')}
                    </DialogDescription>
                </DialogHeader>
                <div className="grid gap-2">
                    <Label htmlFor="return_reason">
                        {t('grades.return_reason')}
                    </Label>
                    <textarea
                        id="return_reason"
                        rows={4}
                        maxLength={REASON_MAX}
                        className={FIELD_CLASS}
                        placeholder={t('grades.return_reason_placeholder')}
                        value={reason}
                        onChange={(event) => setReason(event.target.value)}
                    />
                </div>
                <DialogFooter>
                    <Button
                        variant="outline"
                        disabled={processing}
                        onClick={() => onOpenChange(false)}
                    >
                        {t('common.cancel')}
                    </Button>
                    <Button
                        disabled={processing || !valid}
                        onClick={() => onConfirm(reason.trim())}
                    >
                        {processing ? <Spinner className="mr-1" /> : null}
                        {t('grades.return')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
