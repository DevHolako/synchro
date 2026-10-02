<?php

namespace App\Enums;

/**
 * Where an exam period stands, derived from its dates and its exams (never stored).
 */
enum ExamPeriodStatus: string
{
    case Upcoming = 'upcoming';
    case Ongoing = 'ongoing';
    case Ended = 'ended';
    case Archived = 'archived';
}
