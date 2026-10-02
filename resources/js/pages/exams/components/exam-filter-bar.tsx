import { CalendarDays, Filter, List, RotateCcw } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/i18n/LanguageContext';
import { FIELD_CLASS } from '@/lib/form-classes';
import { EXAM_STATES } from './types';
import type { ExamFilters, ExamListView, ExamOptions } from './types';

interface ExamFilterBarProps {
    filters: ExamFilters;
    /** The program and group pickers; only exam managers get them. */
    options: ExamOptions | null;
    view: ExamListView;
    onChange: (patch: Partial<ExamFilters>) => void;
    onApply: () => void;
    onReset: () => void;
    onViewChange: (view: ExamListView) => void;
}

export function ExamFilterBar({
    filters,
    options,
    view,
    onChange,
    onApply,
    onReset,
    onViewChange,
}: ExamFilterBarProps) {
    const { t } = useTranslation();
    const groups = options?.groups.filter(
        (group) =>
            filters.program_id === '' ||
            group.program_id === Number(filters.program_id),
    );

    return (
        <div className="flex flex-wrap items-center gap-3">
            <div className="w-[170px]">
                <select
                    aria-label={t('common.status')}
                    value={filters.state}
                    onChange={(e) =>
                        onChange({
                            state: e.target.value as ExamFilters['state'],
                        })
                    }
                    className={FIELD_CLASS}
                >
                    <option value="">{t('common.all_statuses')}</option>
                    {EXAM_STATES.map((state) => (
                        <option key={state} value={state}>
                            {t(`exams.state_${state}`)}
                        </option>
                    ))}
                </select>
            </div>

            {options ? (
                <>
                    <div className="w-[200px]">
                        <select
                            aria-label={t('exams.program')}
                            value={filters.program_id}
                            onChange={(e) =>
                                onChange({
                                    program_id: e.target.value,
                                    group_id: '',
                                })
                            }
                            className={FIELD_CLASS}
                        >
                            <option value="">{t('exams.all_programs')}</option>
                            {options.programs.map((program) => (
                                <option key={program.id} value={program.id}>
                                    {program.code} · {program.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="w-[180px]">
                        <select
                            aria-label={t('exams.group')}
                            value={filters.group_id}
                            onChange={(e) =>
                                onChange({ group_id: e.target.value })
                            }
                            className={FIELD_CLASS}
                        >
                            <option value="">{t('exams.all_groups')}</option>
                            {groups?.map((group) => (
                                <option key={group.id} value={group.id}>
                                    {group.name}
                                </option>
                            ))}
                        </select>
                    </div>
                </>
            ) : null}

            <Button size="sm" onClick={onApply}>
                <Filter className="mr-1.5 size-4" />
                {t('common.apply_filters')}
            </Button>
            <Button variant="ghost" size="sm" onClick={onReset}>
                <RotateCcw className="mr-1.5 size-4" />
                {t('common.reset')}
            </Button>

            <div className="flex gap-1 sm:ml-auto">
                <Button
                    variant={view === 'list' ? 'secondary' : 'ghost'}
                    size="sm"
                    aria-pressed={view === 'list'}
                    onClick={() => onViewChange('list')}
                >
                    <List className="mr-1.5 size-4" />
                    {t('exams.view_list')}
                </Button>
                <Button
                    variant={view === 'calendar' ? 'secondary' : 'ghost'}
                    size="sm"
                    aria-pressed={view === 'calendar'}
                    onClick={() => onViewChange('calendar')}
                >
                    <CalendarDays className="mr-1.5 size-4" />
                    {t('exams.view_calendar')}
                </Button>
            </div>
        </div>
    );
}
