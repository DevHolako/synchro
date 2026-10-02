<?php

namespace App\Enums;

/**
 * A student's presence at a course session. A late arrival counts as attended.
 */
enum AttendanceStatus: string
{
    case Present = 'present';
    case Absent = 'absent';
    case Late = 'late';
    case Excused = 'excused';
}
