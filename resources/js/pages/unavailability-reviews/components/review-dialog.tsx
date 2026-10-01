import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
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
import { describeSlot } from '@/pages/unavailabilities/components/unavailability-format';
import { update } from '@/routes/unavailability-reviews';
import type { PendingReview } from './types';

const FIELD_CLASS =
    'w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-800 dark:bg-neutral-900';

interface ReviewDialogProps {
    review: PendingReview | null;
    onClose: () => void;
}

export function ReviewDialog({ review, onClose }: ReviewDialogProps) {
    const { t } = useTranslation();
    const form = useForm({ review_note: '' });
    const errors = form.errors as Record<string, string | undefined>;
    const rejecting = review?.decision === 'rejected';

    const handleClose = () => {
        form.reset();
        form.clearErrors();
        onClose();
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        if (!review) {
            return;
        }

        form.transform((data) => ({ ...data, decision: review.decision }));
        form.patch(update.url(review.unavailability.id), {
            preserveScroll: true,
            onSuccess: handleClose,
        });
    };

    return (
        <Dialog
            open={review !== null}
            onOpenChange={(open) => !open && handleClose()}
        >
            <DialogContent className="sm:max-w-md">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {t(
                                rejecting
                                    ? 'unavailability_reviews.dialog_reject_title'
                                    : 'unavailability_reviews.dialog_approve_title',
                            )}
                        </DialogTitle>
                        {review && (
                            <DialogDescription>
                                {t('unavailability_reviews.dialog_desc', {
                                    teacher: review.unavailability.teacher.name,
                                    when: describeSlot(
                                        review.unavailability,
                                        t,
                                    ),
                                })}
                            </DialogDescription>
                        )}
                    </DialogHeader>

                    <div className="grid gap-2 py-4">
                        <Label htmlFor="review_note">
                            {t('unavailability_reviews.note_label')}
                        </Label>
                        <textarea
                            id="review_note"
                            rows={3}
                            maxLength={1000}
                            value={form.data.review_note}
                            onChange={(e) =>
                                form.setData('review_note', e.target.value)
                            }
                            placeholder={t(
                                rejecting
                                    ? 'unavailability_reviews.note_placeholder_reject'
                                    : 'unavailability_reviews.note_placeholder_approve',
                            )}
                            className={FIELD_CLASS}
                            required={rejecting}
                        />
                        <InputError
                            message={errors.review_note ?? errors.decision}
                        />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={handleClose}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button
                            type="submit"
                            variant={rejecting ? 'destructive' : 'default'}
                            disabled={form.processing}
                        >
                            {form.processing && <Spinner />}
                            {t(
                                rejecting
                                    ? 'unavailability_reviews.reject'
                                    : 'unavailability_reviews.approve',
                            )}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
