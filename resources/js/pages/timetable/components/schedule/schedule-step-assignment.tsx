import { TriangleAlert } from 'lucide-react';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/i18n/LanguageContext';
import type { Assignment, SchedulingOptions } from './types';

const SELECT_CLASS =
    'w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-800 dark:bg-neutral-900';

interface ScheduleStepAssignmentProps {
    options: SchedulingOptions;
    assignment: Assignment;
    /** The teacher the batch will use: the chosen one, or the module's. */
    teacherId: number | null;
    /** The selected groups that belong to the module's program. */
    groupIds: number[];
    onChange: (patch: Partial<Assignment>) => void;
}

const toId = (value: string) => (value === '' ? null : Number(value));

export function ScheduleStepAssignment({
    options,
    assignment,
    teacherId,
    groupIds,
    onChange,
}: ScheduleStepAssignmentProps) {
    const { t } = useTranslation();
    const module = options.modules.find(
        (item) => item.id === assignment.moduleId,
    );
    const programGroups = module
        ? options.groups.filter(
              (group) => group.program_id === module.program_id,
          )
        : [];
    const headcount = programGroups
        .filter((group) => groupIds.includes(group.id))
        .reduce((sum, group) => sum + group.expected_headcount, 0);
    const room = options.rooms.find((item) => item.id === assignment.roomId);

    const toggleGroup = (id: number, checked: boolean) =>
        onChange({
            groupIds: checked
                ? [...groupIds, id]
                : groupIds.filter((groupId) => groupId !== id),
        });

    return (
        <div className="grid gap-4">
            <div className="grid gap-2">
                <Label htmlFor="schedule_module">
                    {t('schedule.field_module')}
                </Label>
                <select
                    id="schedule_module"
                    value={assignment.moduleId ?? ''}
                    onChange={(e) =>
                        onChange({
                            moduleId: toId(e.target.value),
                            teacherId: null,
                        })
                    }
                    className={SELECT_CLASS}
                >
                    <option value="">{t('schedule.pick_module')}</option>
                    {options.modules.map((item) => (
                        <option key={item.id} value={item.id}>
                            {item.code} · {item.name}
                        </option>
                    ))}
                </select>
            </div>

            <fieldset className="grid gap-2">
                <legend className="mb-2 text-sm font-medium">
                    {t('schedule.field_groups')}
                </legend>
                {module ? null : (
                    <p className="text-sm text-neutral-500">
                        {t('schedule.groups_need_module')}
                    </p>
                )}
                {module && programGroups.length === 0 ? (
                    <p className="text-sm text-neutral-500">
                        {t('schedule.groups_none')}
                    </p>
                ) : null}
                <div className="grid max-h-40 gap-2 overflow-y-auto sm:grid-cols-2">
                    {programGroups.map((group) => (
                        <label
                            key={group.id}
                            className="flex items-center gap-2 text-sm"
                        >
                            <Checkbox
                                checked={groupIds.includes(group.id)}
                                onCheckedChange={(checked) =>
                                    toggleGroup(group.id, checked === true)
                                }
                            />
                            <span>
                                {group.name} · {group.expected_headcount}
                            </span>
                        </label>
                    ))}
                </div>
                {module ? (
                    <p className="text-xs text-neutral-500">
                        {t('schedule.groups_hint')}
                    </p>
                ) : null}
            </fieldset>

            <div className="grid gap-2">
                <Label htmlFor="schedule_teacher">
                    {t('schedule.field_teacher')}
                </Label>
                <select
                    id="schedule_teacher"
                    value={teacherId ?? ''}
                    onChange={(e) =>
                        onChange({ teacherId: toId(e.target.value) })
                    }
                    className={SELECT_CLASS}
                >
                    <option value="">{t('schedule.pick_teacher')}</option>
                    {options.teachers.map((teacher) => (
                        <option key={teacher.id} value={teacher.id}>
                            {teacher.name}
                        </option>
                    ))}
                </select>
                <p className="text-xs text-neutral-500">
                    {t('schedule.teacher_default_hint')}
                </p>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="schedule_room">
                    {t('schedule.field_room')}
                </Label>
                <select
                    id="schedule_room"
                    value={assignment.roomId ?? ''}
                    onChange={(e) => onChange({ roomId: toId(e.target.value) })}
                    className={SELECT_CLASS}
                >
                    <option value="">{t('schedule.pick_room')}</option>
                    {options.rooms.map((item) => (
                        <option key={item.id} value={item.id}>
                            {t('schedule.room_option', {
                                name: item.name,
                                building: item.building,
                                capacity: item.course_capacity,
                            })}
                        </option>
                    ))}
                </select>
                {room && headcount > room.course_capacity ? (
                    <p className="flex items-start gap-1.5 text-xs text-amber-700 dark:text-amber-400">
                        <TriangleAlert className="mt-0.5 size-3.5 shrink-0" />
                        {t('schedule.room_too_small', {
                            capacity: room.course_capacity,
                            headcount,
                        })}
                    </p>
                ) : null}
            </div>
        </div>
    );
}
