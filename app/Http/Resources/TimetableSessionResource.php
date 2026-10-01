<?php

namespace App\Http\Resources;

use App\Models\ConflictOverride;
use App\Models\CourseSession;
use App\Models\StudentGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A course session as a calendar event.
 *
 * Times are local wall-clock times without an offset: the calendar renders them as they are,
 * whatever the browser's time zone.
 *
 * @property CourseSession $resource
 */
class TimetableSessionResource extends JsonResource
{
    private const string WALL_CLOCK_FORMAT = 'Y-m-d\TH:i:s';

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $session = $this->resource;

        return [
            'id' => $session->id,
            'start' => $session->starts_at->format(self::WALL_CLOCK_FORMAT),
            'end' => $session->ends_at->format(self::WALL_CLOCK_FORMAT),
            'module' => [
                'id' => $session->module->id,
                'code' => $session->module->code,
                'name' => $session->module->name,
                'color_code' => $session->module->color_code,
            ],
            'teacher' => ['id' => $session->teacher->id, 'name' => $session->teacher->name],
            'room' => [
                'id' => $session->room->id,
                'name' => $session->room->name,
                'building' => $session->room->building->name,
            ],
            'groups' => $session->studentGroups
                ->map(fn (StudentGroup $group): array => ['id' => $group->id, 'name' => $group->name, 'code' => $group->code])
                ->values()
                ->all(),
            'overrides' => $session->conflictOverrides
                ->map(fn (ConflictOverride $override): array => [
                    'id' => $override->id,
                    'type' => $override->conflict_type->value,
                    'justification' => $override->justification,
                ])
                ->values()
                ->all(),
        ];
    }
}
