<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\EmergencyRescheduleExamAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exams\EmergencyRescheduleExamRequest;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ExamEmergencyRescheduleController extends Controller
{
    public function __invoke(EmergencyRescheduleExamRequest $request, Exam $exam, EmergencyRescheduleExamAction $action): RedirectResponse
    {
        $outcome = $action->execute($exam, $request->payload(), $request->reason(), $request->user());

        Inertia::flash('toast', $outcome['released'] === []
            ? ['type' => 'success', 'message' => __('messages.exam_rescheduled')]
            : ['type' => 'warning', 'message' => __('messages.exam_rescheduled_released_invigilators', ['names' => implode(', ', $outcome['released'])])]);

        return back();
    }
}
