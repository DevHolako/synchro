<?php

namespace App\Actions\CalendarFeeds;

use App\Actions\CourseSessions\ListTimetableSessionsAction;
use App\Models\CourseSession;
use App\Models\StudentGroup;
use App\Models\User;
use App\Services\Calendar\ICalendarWriter;
use App\Services\Scheduling\TimetableScope;
use Carbon\CarbonImmutable;

/**
 * A user's own timetable as an RFC 5545 calendar: their group's sessions, or the ones they teach.
 *
 * Sessions are stored as the school's wall-clock time; the feed gives them in UTC so calendar
 * apps place them right whatever their own time zone, without a VTIMEZONE definition.
 */
class BuildCalendarFeedAction
{
    private const int PAST_DAYS = 90;

    private const int FUTURE_DAYS = 365;

    public function __construct(private ListTimetableSessionsAction $listSessions) {}

    public function execute(User $user): string
    {
        $today = CarbonImmutable::today();
        $sessions = $this->listSessions->execute(
            TimetableScope::mine($user),
            $today->subDays(self::PAST_DAYS),
            $today->addDays(self::FUTURE_DAYS),
        );

        $writer = new ICalendarWriter(__('messages.calendar_feed_name', ['app' => config('app.name')]));

        foreach ($sessions as $session) {
            $writer->addEvent(
                uid: "course-session-{$session->id}@".parse_url((string) config('app.url'), PHP_URL_HOST),
                startsAt: $this->toUtc($session->starts_at->format('Y-m-d H:i:s')),
                endsAt: $this->toUtc($session->ends_at->format('Y-m-d H:i:s')),
                summary: "{$session->module->code} · {$session->module->name}",
                location: "{$session->room->name} · {$session->room->building->name}",
                description: $this->description($session),
                sequence: $this->sequence($session),
                lastModified: CarbonImmutable::parse($session->updated_at ?? $session->created_at ?? 'now'),
            );
        }

        return $writer->render();
    }

    private function toUtc(string $wallClock): CarbonImmutable
    {
        return CarbonImmutable::parse($wallClock, (string) config('app.schedule_timezone'))->utc();
    }

    private function description(CourseSession $session): string
    {
        return implode("\n", [
            __('messages.calendar_feed_teacher', ['name' => $session->teacher->name]),
            __('messages.calendar_feed_groups', [
                'groups' => $session->studentGroups->map(fn (StudentGroup $group): string => $group->name)->implode(', '),
            ]),
        ]);
    }

    /**
     * Grows with every change, so calendar apps replace the copy they hold.
     */
    private function sequence(CourseSession $session): int
    {
        if ($session->created_at === null || $session->updated_at === null) {
            return 0;
        }

        return max(0, (int) $session->created_at->diffInSeconds($session->updated_at));
    }
}
