<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\ListExamFormOptionsAction;
use App\Actions\Exams\ListExamPeriodsAction;
use App\Actions\Exams\ListExamsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exams\ExamIndexRequest;
use App\Http\Resources\ExamResource;
use App\Models\Exam;
use App\Models\ExamPeriod;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A period's exams: every state with editing for exam managers, otherwise the published exams
 * concerning the viewer.
 */
class ExamIndexController extends Controller
{
    public function __invoke(
        ExamIndexRequest $request,
        ListExamPeriodsAction $listPeriods,
        ListExamsAction $listExams,
        ListExamFormOptionsAction $listOptions,
    ): Response {
        $viewer = $request->user();
        $periods = $listPeriods->execute();
        $period = $periods->firstWhere('id', $request->periodId()) ?? $listPeriods->current($periods);
        $filters = $request->filters();
        $listing = $period === null ? null : $listExams->execute($viewer, $period, $filters);
        $canManage = $viewer->can('create', Exam::class);

        return Inertia::render('exams/index', [
            'periods' => $periods->map(fn (ExamPeriod $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'session_type' => $item->session_type->value,
                'academic_year' => $item->academic_year,
                'start_date' => $item->start_date->format('Y-m-d'),
                'end_date' => $item->end_date->format('Y-m-d'),
                'status' => $item->status()->value,
                'exams_count' => (int) $item->getAttribute('exams_count'),
            ])->values()->all(),
            'periodId' => $period?->id,
            'exams' => ExamResource::collection($listing['exams'] ?? [])->resolve(),
            'stats' => $listing['stats'] ?? null,
            'filters' => [
                'state' => $filters['state']->value ?? '',
                'program_id' => (string) ($filters['program_id'] ?? ''),
                'group_id' => (string) ($filters['group_id'] ?? ''),
            ],
            'canManage' => $canManage,
            'options' => $canManage ? $listOptions->execute() : null,
        ]);
    }
}
