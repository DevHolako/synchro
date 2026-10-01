<?php

namespace App\Http\Requests\Timetable;

use App\Enums\TimetablePerspective;
use App\Enums\TimetableView;
use App\Http\Requests\Concerns\ReadsTypedInput;
use App\Models\Campus;
use App\Models\CourseSession;
use App\Services\Scheduling\TimetableScope;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TimetableRequest extends FormRequest
{
    use ReadsTypedInput;

    private const string DATE_FORMAT = 'Y-m-d';

    /**
     * Everyone who can read timetables sees their own; any other perspective needs browsing rights.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || ! $user->can('viewAny', CourseSession::class)) {
            return false;
        }

        return $this->perspective() === TimetablePerspective::Mine || $user->can('browse', CourseSession::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'perspective' => ['sometimes', Rule::enum(TimetablePerspective::class)],
            'id' => ['nullable', 'integer', 'min:1'],
            'date' => ['nullable', 'date_format:'.self::DATE_FORMAT],
            'view' => ['nullable', Rule::enum(TimetableView::class)],
        ];
    }

    /**
     * The requested perspective; without one, browsers land on a campus and everyone else on their own timetable.
     */
    public function perspective(): TimetablePerspective
    {
        return TimetablePerspective::tryFrom($this->string('perspective')->value())
            ?? ($this->user()?->can('browse', CourseSession::class)
                ? TimetablePerspective::Campus
                : TimetablePerspective::Mine);
    }

    /**
     * Whose sessions to show. The campus perspective defaults to the first active campus by name.
     */
    public function scope(): TimetableScope
    {
        $perspective = $this->perspective();
        $user = $this->user();

        if ($perspective === TimetablePerspective::Mine && $user !== null) {
            return TimetableScope::mine($user);
        }

        $subjectId = $this->nullableInteger('id');

        if ($perspective === TimetablePerspective::Campus && $subjectId === null) {
            $subjectId = Campus::query()->active()->orderBy('name')->value('id');
        }

        return new TimetableScope($perspective, $subjectId);
    }

    /**
     * The view the user picked, or null to let the client choose by screen size and perspective.
     */
    public function calendarView(): ?TimetableView
    {
        return TimetableView::tryFrom($this->string('view')->value());
    }

    /**
     * The first day of the displayed period: the requested date (today by default) snapped to the view.
     */
    public function anchorDate(): CarbonImmutable
    {
        $date = $this->filled('date')
            ? CarbonImmutable::createFromFormat(self::DATE_FORMAT, $this->string('date')->value())
            : null;

        return $this->effectiveView()->anchor($date ?? CarbonImmutable::today());
    }

    /**
     * The half-open [from, until) range of sessions the displayed period needs.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function range(): array
    {
        return $this->effectiveView()->range($this->anchorDate());
    }

    /**
     * Without a picked view, the client shows a week (grid or list), so load a week.
     */
    private function effectiveView(): TimetableView
    {
        return $this->calendarView() ?? TimetableView::Week;
    }
}
