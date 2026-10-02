<?php

namespace App\Http\Resources;

use App\Models\ExamPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An exam period for the period switcher; load it with `withStatusCounts()` first.
 *
 * @property ExamPeriod $resource
 */
class ExamPeriodResource extends JsonResource
{
    /**
     * @return array{id: int, name: string, session_type: string, academic_year: string, start_date: string, end_date: string, status: string, exams_count: int}
     */
    public function toArray(Request $request): array
    {
        $period = $this->resource;

        return [
            'id' => $period->id,
            'name' => $period->name,
            'session_type' => $period->session_type->value,
            'academic_year' => $period->academic_year,
            'start_date' => $period->start_date->format('Y-m-d'),
            'end_date' => $period->end_date->format('Y-m-d'),
            'status' => $period->status()->value,
            'exams_count' => (int) $period->getAttribute('exams_count'),
        ];
    }
}
