<?php

namespace App\Http\Controllers\Web\Exams;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The signed-in student's convocation for a published exam.
 */
class ExamConvocationDownloadController extends Controller
{
    public function __invoke(Request $request, Exam $exam): StreamedResponse|RedirectResponse
    {
        Gate::authorize('downloadConvocation', $exam);

        $candidate = $exam->candidates()->where('student_id', $request->user()?->id)->firstOrFail();

        if (! Storage::disk('local')->exists($candidate->convocationPath())) {
            Inertia::flash('toast', ['type' => 'info', 'message' => __('messages.exam_document_pending')]);

            return back();
        }

        return Storage::disk('local')->download($candidate->convocationPath(), "convocation-{$exam->module->code}.pdf");
    }
}
