import { Save, Send } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/i18n/LanguageContext';

interface GradeSheetActionsProps {
    changedCount: number;
    incompleteCount: number;
    hasInvalid: boolean;
    processing: boolean;
    onSave: () => void;
    onSubmit: () => void;
}

/** Save the draft, or submit it once every line is saved and complete. */
export function GradeSheetActions({
    changedCount,
    incompleteCount,
    hasInvalid,
    processing,
    onSave,
    onSubmit,
}: GradeSheetActionsProps) {
    const { t } = useTranslation();
    const unsaved = changedCount > 0;
    const blocker = unsaved
        ? t('grades.submit_blocked_unsaved')
        : incompleteCount > 0
          ? t('grades.submit_blocked_incomplete', { count: incompleteCount })
          : null;

    return (
        <div className="sticky bottom-0 flex flex-col gap-2 border-t border-neutral-200 bg-white/95 py-3 backdrop-blur sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800 dark:bg-neutral-950/95">
            <span className="text-sm text-neutral-500">
                {unsaved ? t('grades.unsaved', { count: changedCount }) : null}
                {!unsaved && blocker ? blocker : null}
            </span>
            <div className="flex gap-2">
                <Button
                    variant="outline"
                    disabled={!unsaved || hasInvalid || processing}
                    title={hasInvalid ? t('grades.invalid') : undefined}
                    onClick={onSave}
                >
                    {processing ? (
                        <Spinner className="mr-1" />
                    ) : (
                        <Save className="mr-1 size-4" />
                    )}
                    {t('grades.save_draft')}
                </Button>
                <Button
                    disabled={blocker !== null || processing}
                    title={blocker ?? undefined}
                    onClick={onSubmit}
                >
                    <Send className="mr-1 size-4" />
                    {t('grades.submit')}
                </Button>
            </div>
        </div>
    );
}
