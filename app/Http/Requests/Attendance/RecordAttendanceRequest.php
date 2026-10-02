<?php

namespace App\Http\Requests\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\CourseSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RecordAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('recordAttendance', $this->session()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'marks' => ['present', 'array'],
            'marks.*' => ['array:student_id,status,remarks'],
            'marks.*.student_id' => ['required', 'integer', 'distinct'],
            'marks.*.status' => ['required', Rule::enum(AttendanceStatus::class)],
            'marks.*.remarks' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->session()->hasStarted()) {
                $validator->errors()->add('marks', __('messages.attendance_not_started'));

                return;
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $studentIds = array_column($this->marks(), 'student_id');
            $onRoster = $this->rosterStudentIds($studentIds);

            if (array_diff($studentIds, $onRoster) !== []) {
                $validator->errors()->add('marks', __('messages.attendance_unknown_student'));
            }
        });
    }

    public function session(): CourseSession
    {
        /** @var CourseSession $session */
        $session = $this->route('session');

        return $session;
    }

    /**
     * @return list<array{student_id: int, status: string, remarks: string|null}>
     */
    public function marks(): array
    {
        $marks = [];

        foreach (array_keys((array) $this->input('marks', [])) as $index) {
            $remarks = $this->string("marks.{$index}.remarks")->trim()->value();

            $marks[] = [
                'student_id' => $this->integer("marks.{$index}.student_id"),
                'status' => $this->string("marks.{$index}.status")->value(),
                'remarks' => $remarks === '' ? null : $remarks,
            ];
        }

        return $marks;
    }

    /**
     * Which of the students may be marked: those in the session's groups, or already marked.
     *
     * @param  list<int>  $studentIds
     * @return list<int>
     */
    private function rosterStudentIds(array $studentIds): array
    {
        $session = $this->session();
        $groupIds = $session->studentGroups()->pluck('student_groups.id');

        return array_values(array_map('intval', User::query()
            ->whereKey($studentIds)
            ->where(fn (Builder $query) => $query
                ->whereHas('studentProfile', fn (Builder $profiles) => $profiles->whereIn('student_group_id', $groupIds))
                ->orWhereHas('attendances', fn (Builder $marks) => $marks->where('course_session_id', $session->id)))
            ->pluck('id')
            ->all()));
    }
}
