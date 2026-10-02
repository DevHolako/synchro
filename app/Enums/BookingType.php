<?php

namespace App\Enums;

/**
 * The kinds of booking that occupy teachers, rooms and groups. Values match the morph aliases.
 */
enum BookingType: string
{
    case CourseSession = 'course_session';
    case Exam = 'exam';
}
