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
import { moduleLabel } from '@/lib/module-label';
import {
    archive as archivePeriod,
    destroy as destroyPeriod,
    publish as publishPeriod,
} from '@/routes/exam-periods';
import {
    destroy as destroyExam,
    publish as publishExam,
    schedule as scheduleExam,
    unschedule as unscheduleExam,
} from '@/routes/exams';
import { toastErrors } from '@/lib/toast-errors';
import type { ExamConfirmation } from './types';

const DESTRUCTIVE = new Set<ExamConfirmation['kind']>([
    'delete',
    'delete_period',
]);

function routeOf(confirmation: ExamConfirmation) {
    switch (confirmation.kind) {
        case 'schedule':
            return scheduleExam(confirmation.exam.id);
        case 'unschedule':
            return unscheduleExam(confirmation.exam.id);
        case 'publish':
            return publishExam(confirmation.exam.id);
        case 'delete':
            return destroyExam(confirmation.exam.id);
        case 'publish_period':
            return publishPeriod(confirmation.period.id);
        case 'archive_period':
            return archivePeriod(confirmation.period.id);
        case 'delete_period':
            return destroyPeriod(confirmation.period.id);
    }
}

function subjectOf(confirmation: ExamConfirmation): string {
    return 'exam' in confirmation
        ? moduleLabel(confirmation.exam.module)
        : confirmation.period.name;
}

interface ExamConfirmDialogProps {
    confirmation: ExamConfirmation | null;
    onClose: () => void;
}

export function ExamConfirmDialog({
    confirmation,
    onClose,
}: ExamConfirmDialogProps) {
    const { t } = useTranslation();
    const [processing, setProcessing] = useState(false);

    const handleConfirm = () => {
        if (!confirmation) {
            return;
        }

        router.visit(routeOf(confirmation), {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onSuccess: onClose,
            // Lifecycle refusals (conflicts, wrong state, past start) come back as errors.
            onError: (errors) => {
                toastErrors(errors);
                onClose();
            },
        });
    };

    return (
        <Dialog
            open={confirmation !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent className="sm:max-w-md">
                {confirmation ? (
                    <>
                        <DialogHeader>
                            <DialogTitle>
                                {t(`exams.confirm_${confirmation.kind}_title`)}
                            </DialogTitle>
                            <DialogDescription>
                                {t(`exams.confirm_${confirmation.kind}_desc`, {
                                    subject: subjectOf(confirmation),
                                })}
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button variant="outline" onClick={onClose}>
                                {t('common.cancel')}
                            </Button>
                            <Button
                                variant={
                                    DESTRUCTIVE.has(confirmation.kind)
                                        ? 'destructive'
                                        : 'default'
                                }
                                disabled={processing}
                                onClick={handleConfirm}
                            >
                                {processing && <Spinner />}
                                {t(`exams.confirm_${confirmation.kind}_action`)}
                            </Button>
                        </DialogFooter>
                    </>
                ) : null}
            </DialogContent>
        </Dialog>
    );
}
