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
import { toastErrors } from '@/lib/toast-errors';
import { ExamTimeFields } from '@/pages/exams/components/exam-time-fields';
import { store as storeExam } from '@/routes/exams';
import type { RetakeModule, RetakePeriod } from './types';

const DEFAULT_TIMES = { start: '09:00', end: '11:00' };

interface RetakeExamDialogProps {
    period: RetakePeriod;
    module: RetakeModule | null;
    onClose: () => void;
}

/** Drafts the module's retake exam for the groups of its failing students. */
export function RetakeExamDialog({
    period,
    module,
    onClose,
}: RetakeExamDialogProps) {
    const { t } = useTranslation();
    const [times, setTimes] = useState({
        date: period.start_date,
        ...DEFAULT_TIMES,
    });
    const [processing, setProcessing] = useState(false);

    const save = () => {
        if (module === null) {
            return;
        }

        router.post(
            storeExam.url(),
            {
                exam_period_id: period.id,
                module_id: module.module_id,
                student_group_ids: module.group_ids,
                starts_at: `${times.date} ${times.start}`,
                ends_at: `${times.date} ${times.end}`,
            },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onSuccess: onClose,
                onError: (errors) => toastErrors(errors),
            },
        );
    };

    return (
        <Dialog
            open={module !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {t('retakes.dialog_title', {
                            module: module?.module ?? '',
                        })}
                    </DialogTitle>
                    <DialogDescription>
                        {t('retakes.dialog_desc', { period: period.name })}
                    </DialogDescription>
                </DialogHeader>
                <ExamTimeFields
                    date={times.date}
                    start={times.start}
                    end={times.end}
                    minDate={period.start_date}
                    maxDate={period.end_date}
                    onChange={(field, value) =>
                        setTimes((previous) => ({
                            ...previous,
                            [field]: value,
                        }))
                    }
                />
                <DialogFooter>
                    <Button
                        variant="outline"
                        disabled={processing}
                        onClick={onClose}
                    >
                        {t('common.cancel')}
                    </Button>
                    <Button disabled={processing} onClick={save}>
                        {processing ? <Spinner className="mr-1" /> : null}
                        {t('retakes.save')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
