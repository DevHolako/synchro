<?php

namespace App\Notifications\Data;

/**
 * What every alert about an emergency reschedule says, whoever receives it.
 */
final readonly class RescheduledExam
{
    /**
     * @param  string  $title  The module's label.
     * @param  string  $when  The new date and times, in the alert's language.
     * @param  string  $key  Identifies this reschedule (exam and revision), so each alert goes out once.
     */
    public function __construct(
        public string $title,
        public string $when,
        public string $reason,
        public string $key,
    ) {}
}
