<?php

namespace App\Actions\Grades;

use App\Models\Exam;
use App\Models\ExamDeliberation;
use App\Models\User;

/**
 * The grade sheet a visitor of the grid sees: the module teacher's visit opens it (creating it
 * and adding missing lines); anyone else only reads it once it exists.
 */
class FindGradeSheetAction
{
    public function __construct(private OpenGradeSheetAction $open) {}

    /**
     * @return ExamDeliberation|null Null while the teacher has not opened it yet.
     */
    public function execute(Exam $exam, User $viewer): ?ExamDeliberation
    {
        return $viewer->can('enterGrades', $exam) ? $this->open->execute($exam) : $exam->deliberation;
    }
}
