<?php

namespace App\Enums;

/**
 * Whose timetable is shown: the viewer's own, or one group, teacher, room or campus.
 */
enum TimetablePerspective: string
{
    case Mine = 'mine';
    case Group = 'group';
    case Teacher = 'teacher';
    case Room = 'room';
    case Campus = 'campus';
}
