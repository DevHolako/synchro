<?php

namespace App\Actions\Exams;

use App\Jobs\GenerateConvocationJob;
use App\Jobs\GenerateExamRosterJob;
use App\Models\Exam;
use App\Models\ExamCandidate;
use App\Notifications\ExamConvocationPublishedNotification;
use Illuminate\Support\Facades\DB;

/**
 * Queues a just-published exam's documents: one convocation per candidate and the room sheets.
 * Dispatched after commit, so a rolled-back publication queues nothing (ADR 0012).
 */
class QueueExamDocumentsAction
{
    /**
     * @param  list<int>  $examIds
     */
    public function execute(array $examIds): void
    {
        DB::afterCommit(function () use ($examIds): void {
            ExamCandidate::query()
                ->whereIn('exam_id', $examIds)
                ->with(['student', 'exam.module', 'roomAssignment.room'])
                ->orderBy('id')
                ->each(function (ExamCandidate $candidate): void {
                    GenerateConvocationJob::dispatch($candidate);
                    $candidate->student?->notify(new ExamConvocationPublishedNotification($candidate));
                });

            Exam::query()->whereKey($examIds)->each(fn (Exam $exam) => GenerateExamRosterJob::dispatch($exam));
        });
    }
}
