<?php

namespace App\Enums;

/**
 * The kind of exam period: the regular session or the retake session (rattrapage).
 */
enum ExamSessionType: string
{
    case Normal = 'normal';
    case Rattrapage = 'rattrapage';
}
