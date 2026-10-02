<?php

namespace App\Services\Scheduling;

/**
 * The single entry point for scheduling conflict checks (ADR 0002).
 *
 * Hard conflicts (teacher, room or group double-booking) block a write; soft conflicts
 * (capacity overrun, declared teacher unavailability) warn and may be overridden with a
 * justification. Each occupancy source contributes its bookings, each rule its warnings.
 */
class ConflictDetectorService
{
    /**
     * @var list<OccupancySource>
     */
    private array $sources;

    /**
     * @var list<SoftConflictRule>
     */
    private array $rules;

    public function __construct(
        CourseSessionOccupancy $courseSessions,
        ExamOccupancy $exams,
        CapacityRule $capacity,
        TeacherUnavailabilityRule $teacherUnavailability,
    ) {
        $this->sources = [$courseSessions, $exams];
        $this->rules = [$capacity, $teacherUnavailability];
    }

    public function checkConflicts(BookingSlot $slot): ConflictResult
    {
        $hardConflicts = [];
        $softConflicts = [];

        foreach ($this->sources as $source) {
            array_push($hardConflicts, ...$source->hardConflicts($slot));
        }

        foreach ($this->rules as $rule) {
            array_push($softConflicts, ...$rule->softConflicts($slot));
        }

        return new ConflictResult(hardConflicts: $hardConflicts, softConflicts: $softConflicts);
    }
}
