<?php

namespace App\Enums;

/**
 * The five-state exam lifecycle (ADR 0005): Draft → Scheduled → Published → Completed → Archived.
 *
 * A draft is a sandbox that books nothing; a scheduled exam books its resources but is still
 * internal; candidates only ever see published exams and their history.
 */
enum ExamState: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Completed = 'completed';
    case Archived = 'archived';

    /**
     * The only moves the lifecycle allows; a scheduled exam may go back to draft, nothing else goes back.
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft => $target === self::Scheduled,
            self::Scheduled => $target === self::Draft || $target === self::Published,
            self::Published => $target === self::Completed,
            self::Completed => $target === self::Archived,
            self::Archived => false,
        };
    }

    /**
     * Whether the exam books its groups (and later rooms and invigilators) against other bookings.
     */
    public function occupiesResources(): bool
    {
        return $this !== self::Draft;
    }

    /**
     * Whether the exam may still be edited or deleted; from publication on, only an emergency reschedule changes it.
     */
    public function isEditable(): bool
    {
        return $this === self::Draft || $this === self::Scheduled;
    }

    /**
     * States that book resources.
     *
     * @return list<self>
     */
    public static function occupying(): array
    {
        return array_values(array_filter(self::cases(), fn (self $state): bool => $state->occupiesResources()));
    }

    /**
     * States students and teachers may see: published exams and their history.
     *
     * @return list<self>
     */
    public static function visibleToCandidates(): array
    {
        return [self::Published, self::Completed, self::Archived];
    }
}
