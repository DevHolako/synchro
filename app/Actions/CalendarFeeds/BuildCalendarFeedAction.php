<?php

namespace App\Actions\CalendarFeeds;

use App\Actions\CourseSessions\ListTimetableSessionsAction;
use App\Enums\Permission;
use App\Models\CourseSession;
use App\Models\Exam;
use App\Models\ExamRoomAssignment;
use App\Models\StudentGroup;
use App\Models\User;
use App\Services\Calendar\ICalendarWriter;
use App\Services\Scheduling\TimetableScope;
use App\Support\SchoolClock;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * A user's own timetable as an RFC 5545 calendar: their group's sessions, or the ones they teach,
 * and the published exams concerning them.
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
        $today = SchoolClock::today();
        $sessions = $this->listSessions->execute(
            TimetableScope::mine($user),
            $today->subDays(self::PAST_DAYS),
            $today->addDays(self::FUTURE_DAYS),
        );

        $writer = new ICalendarWriter(__('messages.calendar_feed_name', ['app' => config('app.name')]));
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        foreach ($sessions as $session) {
            $writer->addEvent(
                uid: "course-session-{$session->id}@{$host}",
                startsAt: $this->toUtc($session->starts_at->format('Y-m-d H:i:s')),
                endsAt: $this->toUtc($session->ends_at->format('Y-m-d H:i:s')),
                summary: $session->module->label(),
                location: "{$session->room->name} · {$session->room->building->name}",
                description: $this->description($session),
                sequence: $this->sequence($session),
                lastModified: CarbonImmutable::parse($session->updated_at ?? $session->created_at ?? 'now'),
            );
        }

        foreach ($this->exams($user, $today) as $exam) {
            $writer->addEvent(
                uid: "exam-{$exam->id}@{$host}",
                startsAt: $this->toUtc($exam->starts_at->format('Y-m-d H:i:s')),
                endsAt: $this->toUtc($exam->ends_at->format('Y-m-d H:i:s')),
                summary: __('messages.calendar_feed_exam_summary', ['code' => $exam->module->code, 'name' => $exam->module->name]),
                location: $this->examLocation($exam),
                description: __('messages.calendar_feed_groups', [
                    'groups' => $exam->studentGroups->map(fn (StudentGroup $group): string => $group->name)->implode(', '),
                ]),
                sequence: $this->sequence($exam),
                lastModified: CarbonImmutable::parse($exam->updated_at ?? $exam->created_at ?? 'now'),
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
    private function sequence(CourseSession|Exam $booking): int
    {
        if ($booking->created_at === null || $booking->updated_at === null) {
            return 0;
        }

        return max(0, (int) $booking->created_at->diffInSeconds($booking->updated_at));
    }

    /**
     * Where the user goes: a student's room and seat, an invigilator's room, otherwise the exam's rooms.
     */
    private function examLocation(Exam $exam): string
    {
        $candidate = $exam->candidates->first();

        if ($candidate !== null) {
            return __('messages.calendar_feed_exam_room', [
                'room' => $candidate->roomAssignment->room->name,
                'seat' => $candidate->seat_number,
            ]);
        }

        $invigilator = $exam->invigilators->first();

        if ($invigilator !== null) {
            return $invigilator->roomAssignment->room->name;
        }

        return $exam->roomAssignments->map(fn (ExamRoomAssignment $room): string => $room->room->name)->implode(', ');
    }

    /**
     * The published exams concerning the user, in the feed's window, for those who may see exams.
     *
     * @return Collection<int, Exam>
     */
    private function exams(User $user, CarbonImmutable $today): Collection
    {
        if (! $user->hasPermission(Permission::ViewExams)) {
            return new Collection;
        }

        return Exam::query()
            ->concerning($user)
            ->with([
                'module:id,code,name',
                'studentGroups:id,name',
                'roomAssignments.room:id,name',
                'candidates' => fn ($candidates) => $candidates->where('student_id', $user->id)->with('roomAssignment.room:id,name'),
                'invigilators' => fn ($invigilators) => $invigilators->where('teacher_id', $user->id)->with('roomAssignment.room:id,name'),
            ])
            ->where('starts_at', '>=', $today->subDays(self::PAST_DAYS))
            ->where('starts_at', '<', $today->addDays(self::FUTURE_DAYS))
            ->orderBy('starts_at')
            ->get();
    }
}
