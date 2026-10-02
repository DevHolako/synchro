<?php

namespace App\Actions\Grades;

use App\Models\ExamDeliberation;
use Illuminate\Support\Facades\Storage;

class StoreDeliberationPvAction
{
    public function __construct(private RenderDeliberationPvPdfAction $render) {}

    /**
     * Archive a locked deliberation's PV on the private disk with its SHA-256, once: a
     * redelivered job does nothing.
     *
     * @return bool Whether a PV was written.
     */
    public function execute(ExamDeliberation $deliberation): bool
    {
        $deliberation->refresh();

        if ($deliberation->pv_sha256 !== null) {
            return false;
        }

        $pdf = $this->render->execute($deliberation);

        Storage::disk('local')->put($deliberation->pvPath(), $pdf);

        $deliberation->update([
            'pv_document_path' => $deliberation->pvPath(),
            'pv_sha256' => hash('sha256', $pdf),
        ]);

        return true;
    }
}
