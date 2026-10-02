<?php

namespace App\Http\Resources;

use App\Models\Exam;
use App\Models\StudentGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An exam for the exams page. Times are offset-less wall-clock times, as on the timetable.
 *
 * @property Exam $resource
 */
class ExamResource extends JsonResource
{
    private const string WALL_CLOCK_FORMAT = 'Y-m-d\TH:i:s';

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $exam = $this->resource;

        return [
            'id' => $exam->id,
            'exam_period_id' => $exam->exam_period_id,
            'start' => $exam->starts_at->format(self::WALL_CLOCK_FORMAT),
            'end' => $exam->ends_at->format(self::WALL_CLOCK_FORMAT),
            'state' => $exam->state->value,
            'is_overdue' => $exam->isOverdue(),
            'module' => [
                'id' => $exam->module->id,
                'program_id' => $exam->module->program_id,
                'code' => $exam->module->code,
                'name' => $exam->module->name,
                'color_code' => $exam->module->color_code,
            ],
            'groups' => $exam->studentGroups
                ->map(fn (StudentGroup $group): array => ['id' => $group->id, 'name' => $group->name])
                ->values()
                ->all(),
        ];
    }
}
