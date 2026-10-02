<?php

namespace App\Actions\Exams;

use App\Models\Exam;
use App\Models\ExamRoomAssignment;
use App\Models\StudentProfile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Seats the exam's current candidates (the students of its groups) in its rooms again: each
 * room's range and count, and every candidate's room and seat. Runs inside the caller's transaction.
 */
class ResplitExamAction
{
    public function __construct(private SplitExamRoomsAction $split) {}

    /**
     * @return int How many candidates were seated.
     *
     * @throws ValidationException When the rooms no longer seat every candidate.
     */
    public function execute(Exam $exam): int
    {
        $assignments = $exam->roomAssignments()->with('room:id,exam_capacity')->get();

        $exam->candidates()->delete();

        if ($assignments->isEmpty()) {
            return 0;
        }

        $split = $this->split->execute(
            $this->candidates($exam),
            array_values($assignments->map(fn (ExamRoomAssignment $assignment): array => [
                'room_id' => $assignment->room_id,
                'capacity' => $assignment->room->exam_capacity,
            ])->all()),
            $exam->force_single_room,
        );

        $now = now();
        $rows = [];

        foreach ($split as $index => $room) {
            $assignment = $assignments[$index];
            $assignment->update([
                'allocated_students_count' => count($room['student_ids']),
                'first_surname' => $room['first_surname'],
                'last_surname' => $room['last_surname'],
            ]);

            foreach ($room['student_ids'] as $seat => $studentId) {
                $rows[] = [
                    'exam_id' => $exam->id,
                    'student_id' => $studentId,
                    'exam_room_assignment_id' => $assignment->id,
                    'seat_number' => $seat + 1,
                    'convocation_uuid' => (string) Str::uuid(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            $exam->candidates()->insert($chunk);
        }

        return count($rows);
    }

    /**
     * The students of the exam's groups, with their official names (the display name stands in
     * for a surname that was never recorded).
     *
     * @return list<array{id: int, last_name: string, first_name: string, student_number: string|null}>
     */
    private function candidates(Exam $exam): array
    {
        return array_values(StudentProfile::query()
            ->join('users', 'users.id', '=', 'student_profiles.user_id')
            ->whereIn('student_profiles.student_group_id', $exam->studentGroups()->select('student_groups.id'))
            ->get(['users.id as student_id', 'users.name', 'student_profiles.last_name', 'student_profiles.first_name', 'student_profiles.student_number'])
            ->map(fn (StudentProfile $profile): array => [
                'id' => (int) $profile->getAttribute('student_id'),
                'last_name' => $profile->last_name ?: (string) $profile->getAttribute('name'),
                'first_name' => (string) $profile->first_name,
                'student_number' => $profile->student_number,
            ])
            ->all());
    }
}
