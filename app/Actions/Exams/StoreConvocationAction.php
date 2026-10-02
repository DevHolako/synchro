<?php

namespace App\Actions\Exams;

use App\Models\ExamCandidate;
use Illuminate\Support\Facades\Storage;

class StoreConvocationAction
{
    public function __construct(private RenderConvocationPdfAction $render) {}

    /**
     * Write the candidate's convocation to the private disk, unless it already exists, so a
     * redelivered job does nothing.
     *
     * @return bool Whether a file was written.
     */
    public function execute(ExamCandidate $candidate): bool
    {
        $disk = Storage::disk('local');

        if ($disk->exists($candidate->convocationPath())) {
            return false;
        }

        return $disk->put($candidate->convocationPath(), $this->render->execute($candidate)) !== false;
    }
}
