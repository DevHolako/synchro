<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\EmergencyRescheduleExamAction;
use App\Http\Controllers\Concerns\FlashesExamOutcome;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exams\EmergencyRescheduleExamRequest;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;

class ExamEmergencyRescheduleController extends Controller
{
    use FlashesExamOutcome;

    public function __invoke(EmergencyRescheduleExamRequest $request, Exam $exam, EmergencyRescheduleExamAction $action): RedirectResponse
    {
        $outcome = $action->execute($exam, $request->payload(), $request->reason(), $request->user());

        $this->flashExamOutcome(__('messages.exam_rescheduled'), $outcome['released']);

        return back();
    }
}
