<?php

namespace App\Http\Resources;

use App\Models\ConflictOverride;
use App\Models\CourseSession;
use App\Models\StudentGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property CourseSession $resource
 */
class CourseSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $session = $this->resource;

        return [
            'id' => $session->id,
            'starts_at' => $session->starts_at->toIso8601String(),
            'ends_at' => $session->ends_at->toIso8601String(),
            'module' => $session->relationLoaded('module') && $session->module !== null
                ? new ModuleResource($session->module)
                : null,
            'teacher' => $session->relationLoaded('teacher') && $session->teacher !== null
                ? [
                    'id' => $session->teacher->id,
                    'name' => $session->teacher->name,
                    'email' => $session->teacher->email,
                ]
                : null,
            'room' => $session->relationLoaded('room') && $session->room !== null
                ? new RoomResource($session->room)
                : null,
            'student_groups' => $session->relationLoaded('studentGroups')
                ? $session->studentGroups->map(fn (StudentGroup $group): array => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'code' => $group->code,
                ])->values()->all()
                : [],
            'can_take_attendance' => $request->user()?->can('recordAttendance', $session) ?? false,
            'overrides' => $session->relationLoaded('conflictOverrides')
                ? $session->conflictOverrides->map(fn (ConflictOverride $override): array => [
                    'id' => $override->id,
                    'type' => $override->conflict_type->value,
                    'justification' => $override->justification,
                ])->values()->all()
                : [],
        ];
    }
}
