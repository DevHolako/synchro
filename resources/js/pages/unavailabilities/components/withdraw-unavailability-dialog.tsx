import { router } from '@inertiajs/react';
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
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/i18n/LanguageContext';
import { destroy } from '@/routes/unavailabilities';
import { describePeriod, describeSlot } from './unavailability-format';
import type { Unavailability } from './types';

interface WithdrawUnavailabilityDialogProps {
    unavailability: Unavailability | null;
    onClose: () => void;
}

export function WithdrawUnavailabilityDialog({
    unavailability,
    onClose,
}: WithdrawUnavailabilityDialogProps) {
    const { t, locale } = useTranslation();
    const [processing, setProcessing] = useState(false);

    const handleConfirm = () => {
        if (!unavailability) {
            return;
        }

        router.delete(destroy.url(unavailability.id), {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onSuccess: onClose,
        });
    };

    return (
        <Dialog
            open={unavailability !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {t('unavailabilities.withdraw_title')}
                    </DialogTitle>
                    <DialogDescription>
                        {t('unavailabilities.withdraw_desc')}
                    </DialogDescription>
                </DialogHeader>

                {unavailability && (
                    <p className="py-2 text-sm text-neutral-700 dark:text-neutral-300">
                        {describeSlot(unavailability, t)} ·{' '}
                        {describePeriod(unavailability, t, locale)}
                    </p>
                )}

                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>
                        {t('common.cancel')}
                    </Button>
                    <Button
                        variant="destructive"
                        onClick={handleConfirm}
                        disabled={processing}
                    >
                        {processing && <Spinner />}
                        {t('unavailabilities.withdraw')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
