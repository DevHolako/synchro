<?php

namespace App\Actions\Grades;

use App\Models\ExamDeliberation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StoreDeliberationPvAction
{
    public function __construct(private RenderDeliberationPvPdfAction $render) {}

    /**
     * Archive a locked deliberation's PV on the private disk with its SHA-256, once. The
     * deliberation row is locked while the PV is written, so two deliveries of the job cannot
     * both write it (and leave a file that does not match its hash); a redelivered job does
     * nothing.
     *
     * @return bool Whether a PV was written.
     */
    public function execute(ExamDeliberation $deliberation): bool
    {
        return DB::transaction(function () use ($deliberation): bool {
            $sheet = ExamDeliberation::query()->whereKey($deliberation->id)->lockForUpdate()->firstOrFail();

            if ($sheet->pv_sha256 !== null) {
                return false;
            }

            $pdf = $this->render->execute($sheet);

            Storage::disk('local')->put($sheet->pvPath(), $pdf);

            $sheet->update([
                'pv_document_path' => $sheet->pvPath(),
                'pv_sha256' => hash('sha256', $pdf),
            ]);

            return true;
        });
    }
}
