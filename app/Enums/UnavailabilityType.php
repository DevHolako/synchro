<?php

namespace App\Enums;

enum UnavailabilityType: string
{
    case RecurringWeekly = 'recurring_weekly';
    case AdHocDate = 'ad_hoc_date';
}
