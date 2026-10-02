import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/i18n/LanguageContext';

interface DeliberationLockDialogProps {
    open: boolean;
    processing: boolean;
    onOpenChange: (open: boolean) => void;
    onConfirm: () => void;
}

export function DeliberationLockDialog({
    open,
    processing,
    onOpenChange,
    onConfirm,
}: DeliberationLockDialogProps) {
    const { t } = useTranslation();

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('grades.lock_title')}</DialogTitle>
                    <DialogDescription>
                        {t('grades.lock_desc')}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
                        disabled={processing}
                        onClick={() => onOpenChange(false)}
                    >
                        {t('common.cancel')}
                    </Button>
                    <Button disabled={processing} onClick={onConfirm}>
                        {processing ? <Spinner className="mr-1" /> : null}
                        {t('grades.lock')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
