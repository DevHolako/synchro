import type { TimetableScope } from '../types';
import type { SchedulingPrefill } from './types';

/** The wizard starts from what is on screen: the subject, the highlighted module, the period. */
export function prefillFromScope(
    scope: TimetableScope,
    moduleId: number | null,
    date: string,
): SchedulingPrefill {
    return {
        moduleId,
        groupId: scope.perspective === 'group' ? scope.id : null,
        teacherId: scope.perspective === 'teacher' ? scope.id : null,
        roomId: scope.perspective === 'room' ? scope.id : null,
        date,
    };
}
