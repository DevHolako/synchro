<?php

namespace App\Http\Controllers\Web\Exams;

use App\Actions\Exams\DownloadExamDocumentAction;
use App\Http\Controllers\Concerns\FlashesExamOutcome;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The signed-in student's convocation for a published exam.
 */
class ExamConvocationDownloadController extends Controller
{
    use FlashesExamOutcome;

    public function __invoke(Request $request, Exam $exam, DownloadExamDocumentAction $download): StreamedResponse|RedirectResponse
    {
        Gate::authorize('downloadConvocation', $exam);

        $candidate = $exam->candidates()->where('student_id', $request->user()?->id)->firstOrFail();

        return $download->execute(
            $candidate->convocationPath(),
            __('documents.convocation_filename', ['code' => $exam->module->code], 'fr'),
        ) ?? $this->documentPending();
    }
}
