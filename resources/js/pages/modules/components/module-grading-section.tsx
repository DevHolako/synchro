import { AlertCircle } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/i18n/LanguageContext';

interface ModuleGradingSectionProps {
    continuousAssessmentWeight: string;
    maxWeight: number;
    error?: string;
    onChange: (value: string) => void;
}

/**
 * Whether a typed continuous assessment (CC) weight is a whole percentage the server accepts.
 */
export function isWeightInRange(value: string, maxWeight: number): boolean {
    const weight = Number(value);

    return (
        value.trim() !== '' &&
        Number.isInteger(weight) &&
        weight >= 0 &&
        weight <= maxWeight
    );
}

export function ModuleGradingSection({
    continuousAssessmentWeight,
    maxWeight,
    error,
    onChange,
}: ModuleGradingSectionProps) {
    const { t } = useTranslation();
    const isInRange = isWeightInRange(continuousAssessmentWeight, maxWeight);

    return (
        <div className="rounded-lg border border-neutral-200 bg-neutral-50/50 p-3 dark:border-neutral-800 dark:bg-neutral-800/40">
            <div className="mb-2 text-xs font-semibold text-neutral-800 dark:text-neutral-200">
                {t('modules.dialog_grading_section')}
            </div>

            <div className="grid grid-cols-2 gap-3">
                <div className="grid gap-1">
                    <Label htmlFor="mod_cc_weight" className="text-xs">
                        {t('modules.dialog_continuous_assessment_weight')}
                    </Label>
                    <Input
                        id="mod_cc_weight"
                        type="number"
                        min="0"
                        max={maxWeight}
                        step="1"
                        value={continuousAssessmentWeight}
                        onChange={(e) => onChange(e.target.value)}
                        required
                    />
                </div>

                <div className="grid gap-1">
                    <Label htmlFor="mod_exam_weight" className="text-xs">
                        {t('modules.dialog_exam_weight')}
                    </Label>
                    <Input
                        id="mod_exam_weight"
                        value={
                            isInRange
                                ? 100 - Number(continuousAssessmentWeight)
                                : '—'
                        }
                        readOnly
                        tabIndex={-1}
                        className="bg-neutral-100 dark:bg-neutral-800"
                    />
                </div>
            </div>

            {isInRange ? (
                <p className="mt-2 text-xs text-neutral-500">
                    {t('modules.dialog_grading_hint')}
                </p>
            ) : (
                <div className="mt-2 flex items-center gap-1.5 text-xs font-medium text-red-600">
                    <AlertCircle className="size-3.5" />
                    {t('modules.dialog_weight_out_of_range', {
                        max: maxWeight,
                    })}
                </div>
            )}

            {error && <p className="mt-1 text-xs text-red-500">{error}</p>}
        </div>
    );
}
