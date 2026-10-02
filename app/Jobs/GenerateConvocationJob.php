<?php

namespace App\Jobs;

use App\Actions\Exams\StoreConvocationAction;
use App\Models\ExamCandidate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateConvocationJob implements ShouldQueue
{
    use Queueable;

    /**
     * One page renders in well under a second; must stay below the `supervisor-default` timeout.
     */
    public int $timeout = 45;

    /** A candidate seated again before the job ran has a new convocation of its own. */
    public bool $deleteWhenMissingModels = true;

    public function __construct(public ExamCandidate $candidate)
    {
        $this->onQueue('default');
    }

    public function handle(StoreConvocationAction $action): void
    {
        $action->execute($this->candidate);
    }

    /**
     * Record the convocation that could not be generated; its download stays "being prepared".
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('Convocation not generated', ['candidate' => $this->candidate->id, 'error' => $exception?->getMessage()]);
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return ['exam:'.$this->candidate->exam_id, 'convocation'];
    }
}
