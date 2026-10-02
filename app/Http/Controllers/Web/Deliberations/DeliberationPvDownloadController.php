<?php

namespace App\Http\Controllers\Web\Deliberations;

use App\Actions\Exams\DownloadExamDocumentAction;
use App\Http\Controllers\Concerns\FlashesExamToasts;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamDeliberation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A locked deliberation's official PV, for coordinators, exam managers and the module teacher.
 */
class DeliberationPvDownloadController extends Controller
{
    use FlashesExamToasts;

    public function __invoke(Exam $exam, DownloadExamDocumentAction $download): StreamedResponse|RedirectResponse
    {
        Gate::authorize('downloadPv', $exam);

        /** @var ExamDeliberation $deliberation */
        $deliberation = $exam->deliberation;

        return $download->execute(
            $deliberation->pvPath(),
            __('documents.pv_filename', ['code' => $exam->module->code], 'fr'),
        ) ?? $this->documentPending();
    }
}
