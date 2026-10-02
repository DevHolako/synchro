<?php

namespace App\Http\Controllers\Web\Exams;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * An exam's door lists and attendance sheets, for exam managers and its invigilators.
 */
class ExamRosterDownloadController extends Controller
{
    public function __invoke(Exam $exam): StreamedResponse|RedirectResponse
    {
        Gate::authorize('downloadRoster', $exam);

        if (! Storage::disk('local')->exists($exam->rosterPath())) {
            Inertia::flash('toast', ['type' => 'info', 'message' => __('messages.exam_document_pending')]);

            return back();
        }

        return Storage::disk('local')->download($exam->rosterPath(), "emargement-{$exam->module->code}.pdf");
    }
}
