import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { useTranslation } from '@/i18n/LanguageContext';
import { FIELD_CLASS } from '@/lib/form-classes';
import { BROWSE_PERSPECTIVES } from './types';
import type { ScopePerspective, TimetableOptions } from './types';

interface SubjectOption {
    id: number;
    label: string;
}

function subjectOptions(
    perspective: ScopePerspective,
    options: TimetableOptions,
): SubjectOption[] {
    switch (perspective) {
        case 'campus':
            return options.campuses.map((campus) => ({
                id: campus.id,
                label: campus.name,
            }));
        case 'group':
            return options.groups.map((group) => ({
                id: group.id,
                label: `${group.name} · ${group.academic_year}`,
            }));
        case 'teacher':
            return options.teachers.map((teacher) => ({
                id: teacher.id,
                label: teacher.name,
            }));
        case 'room':
            return options.rooms.map((room) => ({
                id: room.id,
                label: `${room.name} · ${room.building}`,
            }));
    }
}

interface TimetableFilterBarProps {
    perspective: ScopePerspective;
    subjectId: number | null;
    options: TimetableOptions;
    onPerspectiveChange: (perspective: ScopePerspective) => void;
    onSubjectChange: (id: number | null) => void;
}

export function TimetableFilterBar({
    perspective,
    subjectId,
    options,
    onPerspectiveChange,
    onSubjectChange,
}: TimetableFilterBarProps) {
    const { t } = useTranslation();

    return (
        <div className="flex flex-wrap items-center gap-3 rounded-lg border border-neutral-200 bg-white p-4 shadow-xs dark:border-neutral-800 dark:bg-neutral-900">
            <ToggleGroup
                type="single"
                variant="outline"
                value={perspective}
                aria-label={t('timetable.perspective_label')}
                onValueChange={(value) =>
                    value && onPerspectiveChange(value as ScopePerspective)
                }
            >
                {BROWSE_PERSPECTIVES.map((item) => (
                    <ToggleGroupItem key={item} value={item} className="px-3">
                        {t(`timetable.perspective_${item}`)}
                    </ToggleGroupItem>
                ))}
            </ToggleGroup>

            <div className="min-w-[220px] flex-1 sm:max-w-[320px]">
                <select
                    aria-label={t(`timetable.pick_${perspective}`)}
                    value={subjectId ?? ''}
                    onChange={(e) =>
                        onSubjectChange(
                            e.target.value === ''
                                ? null
                                : Number(e.target.value),
                        )
                    }
                    className={FIELD_CLASS}
                >
                    <option value="">
                        {t(`timetable.pick_${perspective}`)}
                    </option>
                    {subjectOptions(perspective, options).map((option) => (
                        <option key={option.id} value={option.id}>
                            {option.label}
                        </option>
                    ))}
                </select>
            </div>
        </div>
    );
}
