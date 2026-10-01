<?php

namespace App\Enums;

/**
 * The resource a scheduling conflict is about (ADR 0002).
 */
enum ConflictType: string
{
    // Hard conflicts: physical double-booking.
    case Teacher = 'teacher';
    case Room = 'room';
    case Group = 'group';
}
