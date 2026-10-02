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

interface GradeSubmitDialogProps {
    open: boolean;
    processing: boolean;
    onOpenChange: (open: boolean) => void;
    onConfirm: () => void;
}

export function GradeSubmitDialog({
    open,
    processing,
    onOpenChange,
    onConfirm,
}: GradeSubmitDialogProps) {
    const { t } = useTranslation();

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('grades.submit_title')}</DialogTitle>
                    <DialogDescription>
                        {t('grades.submit_desc')}
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
                        {t('grades.submit')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
