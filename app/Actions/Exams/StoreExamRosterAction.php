<?php

namespace App\Actions\Exams;

use App\Models\Exam;
use Illuminate\Support\Facades\Storage;

class StoreExamRosterAction
{
    public function __construct(private RenderExamRosterPdfAction $render) {}

    /**
     * Write the exam's door lists and attendance sheets to the private disk, unless they already
     * exist, so a redelivered job does nothing.
     *
     * @return bool Whether a file was written.
     */
    public function execute(Exam $exam): bool
    {
        $disk = Storage::disk('local');

        if ($disk->exists($exam->rosterPath())) {
            return false;
        }

        return $disk->put($exam->rosterPath(), $this->render->execute($exam)) !== false;
    }
}
