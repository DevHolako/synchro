<?php

use App\Actions\CalendarFeeds\IssueCalendarFeedTokenAction;
use App\Models\CourseSession;
use App\Models\Module;
use App\Models\Program;
use App\Models\StudentGroup;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo('2026-10-07 09:00');
    // A zone with a stable, well-known rule; Morocco's own rule changes with government decisions.
    config(['app.schedule_timezone' => 'Europe/Paris', 'app.url' => 'https://synchro.test']);
    $this->teacher = User::factory()->teacher()->create();
    $program = Program::factory()->create();
    $this->module = Module::factory()->create(['program_id' => $program->id, 'code' => 'ALG-101', 'name' => 'Algorithmes, structures; et données']);
    $this->group = StudentGroup::factory()->create(['program_id' => $program->id]);
    $this->session = feedSession('2026-10-12 10:00', '2026-10-12 12:00');
});

function feedSession(string $startsAt, string $endsAt, array $attributes = []): CourseSession
{
    return CourseSession::factory()->between($startsAt, $endsAt)->forGroups(test()->group)->create([
        'module_id' => test()->module->id,
        'teacher_id' => test()->teacher->id,
        ...$attributes,
    ]);
}

function feedUrl(User $user): string
{
    return route('calendar-feeds.show', ['token' => app(IssueCalendarFeedTokenAction::class)->execute($user)]);
}

/**
 * The content lines of the feed, unfolded.
 *
 * @return list<string>
 */
function feedLines(string $body): array
{
    return explode("\r\n", rtrim(str_replace("\r\n ", '', $body), "\r\n"));
}

test('a user creates their link from the timetable and sees it as https and webcal', function () {
    $this->actingAs($this->teacher)
        ->post(route('calendar-feed.store'))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->teacher->refresh();

    expect($this->teacher->calendar_feed_token_hash)->toBe(hash('sha256', (string) $this->teacher->calendar_feed_token));

    $url = route('calendar-feeds.show', ['token' => $this->teacher->calendar_feed_token]);

    $this->actingAs($this->teacher)
        ->get(route('timetable.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('calendarFeed.https', $url)
            ->where('calendarFeed.webcal', preg_replace('#^https?://#', 'webcal://', $url)));
});

test('a teacher feed is a valid calendar of the sessions they teach, in UTC', function () {
    feedSession('2026-10-13 10:00', '2026-10-13 12:00', ['teacher_id' => User::factory()->teacher()->create()->id]);

    $response = $this->get(feedUrl($this->teacher))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/calendar; charset=utf-8');

    $body = $response->getContent();
    $lines = feedLines($body);

    expect($body)->toStartWith("BEGIN:VCALENDAR\r\nVERSION:2.0\r\n")
        ->and($body)->toEndWith("END:VCALENDAR\r\n")
        ->and(substr_count($body, 'BEGIN:VEVENT'))->toBe(1)
        ->and($lines)->toContain("UID:course-session-{$this->session->id}@synchro.test")
        // 10:00 in Paris (summer time, UTC+2) is 08:00 UTC.
        ->and($lines)->toContain('DTSTART:20261012T080000Z')
        ->and($lines)->toContain('DTEND:20261012T100000Z')
        ->and($lines)->toContain('SUMMARY:ALG-101 · Algorithmes\, structures\; et données');
});

test('feed lines are folded at 75 octets without splitting characters', function () {
    $this->module->update(['name' => trim(str_repeat('Systèmes répartis et parallèles ', 6))]);

    $body = $this->get(feedUrl($this->teacher))->getContent();

    foreach (explode("\r\n", $body) as $line) {
        expect(strlen($line))->toBeLessThanOrEqual(75)
            ->and(mb_check_encoding($line, 'UTF-8'))->toBeTrue();
    }

    expect(feedLines($body))->toContain('SUMMARY:ALG-101 · '.trim(str_repeat('Systèmes répartis et parallèles ', 6)));
});

test('a student feed follows their group, and winter time shifts by one hour less', function () {
    feedSession('2026-11-02 10:00', '2026-11-02 12:00');
    $student = StudentProfile::factory()->create(['student_group_id' => $this->group->id])->user;

    // Paris is back on UTC+1 after 2026-10-25.
    expect(feedLines($this->get(feedUrl($student))->getContent()))->toContain('DTSTART:20261102T090000Z');
});

test('the school time zone is configurable', function () {
    config(['app.schedule_timezone' => 'UTC']);
    $student = StudentProfile::factory()->create(['student_group_id' => $this->group->id])->user;

    expect(feedLines($this->get(feedUrl($student))->getContent()))->toContain('DTSTART:20261012T100000Z');
});

test('a moved session keeps its uid with a higher sequence, and a deleted one disappears', function () {
    $url = feedUrl($this->teacher);
    $sequenceOf = fn (string $body) => (int) str(collect(feedLines($body))->first(fn ($line) => str_starts_with($line, 'SEQUENCE:')))->after(':')->value();
    $before = $sequenceOf($this->get($url)->getContent());

    $this->travel(5)->minutes();
    $this->session->update(['starts_at' => '2026-10-14 14:00', 'ends_at' => '2026-10-14 16:00']);
    $after = $this->get($url)->getContent();

    expect(feedLines($after))->toContain("UID:course-session-{$this->session->id}@synchro.test")
        ->and(feedLines($after))->toContain('DTSTART:20261014T120000Z')
        ->and($sequenceOf($after))->toBeGreaterThan($before);

    $this->session->studentGroups()->detach();
    $this->session->delete();

    expect($this->get($url)->getContent())->not->toContain('BEGIN:VEVENT');
});

test('changing only a session groups still raises its sequence', function () {
    $url = feedUrl($this->teacher);
    $sequenceOf = fn (string $body) => (int) str(collect(feedLines($body))->first(fn ($line) => str_starts_with($line, 'SEQUENCE:')))->after(':')->value();
    $before = $sequenceOf($this->get($url)->getContent());

    $this->travel(5)->minutes();
    $this->session->room->update(['course_capacity' => 500, 'exam_capacity' => 100]);
    $this->module->update(['is_active' => true]);

    // The same times, room and teacher: only the groups change, through the real update route.
    $this->actingAs(User::factory()->coordinator()->create())
        ->put(route('course-sessions.update', $this->session), [
            'module_id' => $this->session->module_id,
            'teacher_id' => $this->session->teacher_id,
            'room_id' => $this->session->room_id,
            'student_group_ids' => [$this->group->id, StudentGroup::factory()->create(['program_id' => $this->group->program_id])->id],
            'starts_at' => '2026-10-12 10:00',
            'ends_at' => '2026-10-12 12:00',
        ])
        ->assertSessionHasNoErrors();

    expect($sequenceOf($this->get($url)->getContent()))->toBeGreaterThan($before);
});

test('an account that may no longer read timetables gets nothing', function () {
    $url = feedUrl($this->teacher);
    // Every role holds ViewSchedules today, so the loss of the right is simulated at the gate.
    Gate::before(fn (User $user, string $ability) => $ability === 'viewAny' ? false : null);

    $this->get($url)->assertNotFound();
});

test('a replaced, revoked or unknown token gets nothing', function () {
    $old = feedUrl($this->teacher);
    $current = feedUrl($this->teacher);

    $this->get($old)->assertNotFound();
    $this->get($current)->assertOk();

    $this->actingAs($this->teacher)->delete(route('calendar-feed.destroy'))->assertRedirect();

    $this->get($current)->assertNotFound();
    $this->get(route('calendar-feeds.show', ['token' => 'unknown']))->assertNotFound();
});

test('an account that is not active gets nothing', function () {
    $url = feedUrl($this->teacher);
    $this->teacher->forceFill(['status' => 'invited'])->save();

    $this->get($url)->assertNotFound();
});
