<?php

namespace App\Actions\Exams;

use App\Enums\Permission;
use App\Models\ExamCandidate;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FindCandidateConvocationAction
{
    public function __construct(private readonly DownloadExamDocumentAction $download) {}

    /**
     * Resolve and stream a candidate convocation PDF, scoped strictly to prevent IDOR.
     *
     * @throws ModelNotFoundException
     */
    public function execute(int $id, User $user): ?StreamedResponse
    {
        // 1. Resolve $id as Exam ID for the authenticated user
        $candidate = ExamCandidate::query()
            ->where('exam_id', $id)
            ->where('student_id', $user->id)
            ->with(['exam.module', 'roomAssignment.room'])
            ->first();

        // 2. If not found, allow exam managers to look up by candidate ID
        if ($candidate === null && $user->hasPermission(Permission::ManageExams)) {
            $candidate = ExamCandidate::query()
                ->whereKey($id)
                ->with(['exam.module', 'roomAssignment.room'])
                ->first();
        }

        if ($candidate === null) {
            throw new ModelNotFoundException(__('messages.convocation_not_found'));
        }

        Gate::authorize('downloadConvocation', $candidate->exam);

        return $this->download->execute(
            $candidate->convocationPath(),
            __('documents.convocation_filename', ['code' => $candidate->exam->module->code], 'fr'),
        );
    }
}
