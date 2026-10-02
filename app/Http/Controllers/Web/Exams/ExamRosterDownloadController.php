<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\DownloadExamDocumentAction;
use App\Http\Controllers\Concerns\FlashesExamOutcome;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * An exam's door lists and attendance sheets, for exam managers and its invigilators.
 */
class ExamRosterDownloadController extends Controller
{
    use FlashesExamOutcome;

    public function __invoke(Exam $exam, DownloadExamDocumentAction $download): StreamedResponse|RedirectResponse
    {
        Gate::authorize('downloadRoster', $exam);

        return $download->execute(
            $exam->rosterPath(),
            __('documents.roster_filename', ['code' => $exam->module->code], 'fr'),
        ) ?? $this->documentPending();
    }
}
