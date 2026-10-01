<?php

namespace App\Services\Scheduling;

/**
 * The single entry point for scheduling conflict checks (ADR 0002).
 *
 * Hard conflicts (teacher, room or group double-booking) block a write; soft conflicts
 * (Ticket 03) warn and may be overridden. Each occupancy source contributes its bookings.
 */
class ConflictDetectorService
{
    /**
     * @var list<OccupancySource>
     */
    private array $sources;

    public function __construct(CourseSessionOccupancy $courseSessions)
    {
        $this->sources = [$courseSessions];
    }

    public function checkConflicts(SessionSlot $slot): ConflictResult
    {
        $hardConflicts = [];

        foreach ($this->sources as $source) {
            array_push($hardConflicts, ...$source->hardConflicts($slot));
        }

        return new ConflictResult(hardConflicts: $hardConflicts);
    }
}
