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
import { ScheduleStepAssignment } from './schedule-step-assignment';
import { ScheduleStepDates } from './schedule-step-dates';
import { ScheduleStepReview } from './schedule-step-review';
import type { TimetableLimits } from '../types';
import type { SchedulingOptions, SchedulingPrefill } from './types';
import { WIZARD_STEPS, useScheduleWizard } from './use-schedule-wizard';

interface ScheduleSessionsDialogProps {
    /** Undefined while the options load. */
    options: SchedulingOptions | null | undefined;
    limits: TimetableLimits;
    prefill: SchedulingPrefill;
    canOverride: boolean;
    onClose: () => void;
    /** Called with the first new session's date once the batch is saved. */
    onScheduled: (firstDate: string) => void;
}

/** The batch scheduling wizard: module and resources, dates and times, then review. */
export function ScheduleSessionsDialog({
    options,
    limits,
    prefill,
    canOverride,
    onClose,
    onScheduled,
}: ScheduleSessionsDialogProps) {
    const { t } = useTranslation();
    const wizard = useScheduleWizard({
        options,
        limits,
        prefill,
        canOverride,
        onScheduled,
    });
    const lastStep = wizard.step === WIZARD_STEPS.length - 1;

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{t('schedule.title')}</DialogTitle>
                    <DialogDescription>
                        {t('schedule.step_counter', {
                            current: wizard.step + 1,
                            total: WIZARD_STEPS.length,
                        })}{' '}
                        · {t(`schedule.step_${WIZARD_STEPS[wizard.step]}`)}
                    </DialogDescription>
                </DialogHeader>

                {options ? null : (
                    <p className="flex items-center gap-2 py-8 text-sm text-neutral-500">
                        <Spinner />
                        {t('schedule.loading_options')}
                    </p>
                )}

                {options && wizard.step === 0 ? (
                    <ScheduleStepAssignment
                        options={options}
                        assignment={wizard.assignment}
                        teacherId={wizard.teacherId}
                        groupIds={wizard.groupIds}
                        onChange={wizard.updateAssignment}
                    />
                ) : null}

                {options && wizard.step === 1 ? (
                    <ScheduleStepDates
                        rule={wizard.rule}
                        dates={wizard.dates}
                        ranges={wizard.ranges}
                        rangesValid={wizard.rangesValid}
                        slotCount={wizard.slots.length}
                        limits={limits}
                        totalMinutes={wizard.totalMinutes}
                        onRuleChange={wizard.updateRule}
                        onRemoveDate={wizard.removeDate}
                        onAddDate={wizard.addDate}
                        onRangeChange={wizard.updateRange}
                        onAddRange={wizard.addRange}
                        onRemoveRange={wizard.removeRange}
                    />
                ) : null}

                {options && lastStep ? (
                    <ScheduleStepReview
                        response={wizard.response}
                        checking={wizard.checking}
                        errors={wizard.errors}
                        canOverride={canOverride}
                        limits={limits}
                        justification={wizard.justification}
                        onJustificationChange={wizard.setJustification}
                        onRemoveSlot={wizard.removeSlot}
                    />
                ) : null}

                <DialogFooter className="gap-2">
                    {wizard.step > 0 ? (
                        <Button
                            type="button"
                            variant="outline"
                            onClick={wizard.back}
                            disabled={wizard.submitting}
                        >
                            {t('schedule.back')}
                        </Button>
                    ) : null}
                    {lastStep ? (
                        <Button
                            type="button"
                            onClick={wizard.submit}
                            disabled={!wizard.canSubmit}
                        >
                            {wizard.submitting ? <Spinner /> : null}
                            {t('schedule.submit', {
                                count: wizard.slots.length,
                            })}
                        </Button>
                    ) : (
                        <Button
                            type="button"
                            onClick={wizard.next}
                            disabled={!options || !wizard.canContinue}
                        >
                            {t('schedule.next')}
                        </Button>
                    )}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
