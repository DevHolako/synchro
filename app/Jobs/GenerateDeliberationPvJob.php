<?php

namespace App\Jobs;

use App\Actions\Grades\StoreDeliberationPvAction;
use App\Models\ExamDeliberation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateDeliberationPvJob implements ShouldQueue
{
    use Queueable;

    /**
     * Must stay below the `supervisor-default` timeout.
     */
    public int $timeout = 45;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public ExamDeliberation $deliberation)
    {
        $this->onQueue('default');
    }

    public function handle(StoreDeliberationPvAction $action): void
    {
        $action->execute($this->deliberation);
    }

    /**
     * Record the PV that could not be generated; its download stays "being prepared".
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('Deliberation PV not generated', ['exam' => $this->deliberation->exam_id, 'error' => $exception?->getMessage()]);
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return ['exam:'.$this->deliberation->exam_id, 'pv'];
    }
}
