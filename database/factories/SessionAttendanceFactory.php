<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\CourseSession;
use App\Models\SessionAttendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionAttendance>
 */
class SessionAttendanceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_session_id' => CourseSession::factory(),
            'student_id' => User::factory()->student(),
            'status' => AttendanceStatus::Present,
            'remarks' => null,
            'recorded_by' => User::factory()->teacher(),
        ];
    }
}
