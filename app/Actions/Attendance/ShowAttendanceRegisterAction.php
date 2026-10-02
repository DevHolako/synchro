<?php

namespace App\Actions\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\CourseSession;
use App\Models\SessionAttendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * A session's attendance register: every student of its groups (plus anyone already marked
 * who has since changed group), their mark, and how often they missed this module.
 */
class ShowAttendanceRegisterAction
{
    /**
     * @return list<array{student_id: int, name: string, student_number: string|null, group: string|null, status: string|null, remarks: string|null, module_absences: int, module_recorded: int}>
     */
    public function execute(CourseSession $session): array
    {
        $marks = $session->attendances()->get()->keyBy('student_id');
        $groupIds = $session->studentGroups()->pluck('student_groups.id');

        $students = User::query()
            ->where(fn (Builder $query) => $query
                ->whereHas('studentProfile', fn (Builder $profiles) => $profiles->whereIn('student_group_id', $groupIds))
                ->orWhereIn('id', $marks->keys()))
            ->with('studentProfile.studentGroup:id,name')
            ->orderBy('name')
            ->get(['id', 'name']);

        $history = $this->moduleHistory($session->module_id, $students->pluck('id')->all());

        return array_values($students->map(function (User $student) use ($marks, $history): array {
            /** @var SessionAttendance|null $mark */
            $mark = $marks->get($student->id);

            return [
                'student_id' => $student->id,
                'name' => $student->name,
                'student_number' => $student->studentProfile?->student_number,
                'group' => $student->studentProfile?->studentGroup?->name,
                'status' => $mark?->status->value,
                'remarks' => $mark?->remarks,
                'module_absences' => $history[$student->id]['absences'] ?? 0,
                'module_recorded' => $history[$student->id]['recorded'] ?? 0,
            ];
        })->all());
    }

    /**
     * Each student's marks across all sessions of the module: how many, and how many absences.
     *
     * @param  array<int, int>  $studentIds
     * @return array<int, array{absences: int, recorded: int}>
     */
    private function moduleHistory(int $moduleId, array $studentIds): array
    {
        return DB::table('session_attendances')
            ->join('course_sessions', 'course_sessions.id', '=', 'session_attendances.course_session_id')
            ->where('course_sessions.module_id', $moduleId)
            ->whereIn('session_attendances.student_id', $studentIds)
            ->groupBy('session_attendances.student_id')
            ->selectRaw('session_attendances.student_id as student_id, count(*) as recorded')
            ->selectRaw('sum(case when session_attendances.status = ? then 1 else 0 end) as absences', [AttendanceStatus::Absent->value])
            ->get()
            ->mapWithKeys(fn (object $row): array => [
                (int) $row->student_id => ['absences' => (int) $row->absences, 'recorded' => (int) $row->recorded],
            ])
            ->all();
    }
}
