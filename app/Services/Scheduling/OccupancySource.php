<?php

namespace App\Services\Scheduling;

/**
 * A kind of booking that occupies teachers, rooms and groups (course sessions now, exams in Part 04).
 */
interface OccupancySource
{
    /**
     * Bookings of this kind that physically collide with the slot.
     *
     * @return list<Conflict>
     */
    public function hardConflicts(SessionSlot $slot): array;
}
