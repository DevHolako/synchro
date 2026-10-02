import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/i18n/LanguageContext';
import { GRID_END, GRID_START, GRID_STEP_SECONDS } from '@/lib/scheduling-grid';

interface ExamTimeFieldsProps {
    date: string;
    start: string;
    end: string;
    /** The period's first and last days bound the date. */
    minDate: string;
    maxDate: string;
    onChange: (field: 'date' | 'start' | 'end', value: string) => void;
}

export function ExamTimeFields({
    date,
    start,
    end,
    minDate,
    maxDate,
    onChange,
}: ExamTimeFieldsProps) {
    const { t } = useTranslation();

    return (
        <div className="grid grid-cols-3 gap-3">
            <div className="grid gap-2">
                <Label htmlFor="exam_date">{t('exams.date')}</Label>
                <Input
                    id="exam_date"
                    type="date"
                    min={minDate}
                    max={maxDate}
                    value={date}
                    onChange={(e) => onChange('date', e.target.value)}
                    required
                />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="exam_start">{t('exams.start')}</Label>
                <Input
                    id="exam_start"
                    type="time"
                    step={GRID_STEP_SECONDS}
                    min={GRID_START}
                    max={GRID_END}
                    value={start}
                    onChange={(e) => onChange('start', e.target.value)}
                    required
                />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="exam_end">{t('exams.end')}</Label>
                <Input
                    id="exam_end"
                    type="time"
                    step={GRID_STEP_SECONDS}
                    min={GRID_START}
                    max={GRID_END}
                    value={end}
                    onChange={(e) => onChange('end', e.target.value)}
                    required
                />
            </div>
        </div>
    );
}
