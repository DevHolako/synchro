<?php

namespace App\Jobs;

use App\Actions\Exams\StoreExamRosterAction;
use App\Models\Exam;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateExamRosterJob implements ShouldQueue
{
    use Queueable;

    /**
     * Must stay below the `supervisor-default` timeout.
     */
    public int $timeout = 45;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public Exam $exam)
    {
        $this->onQueue('default');
    }

    public function handle(StoreExamRosterAction $action): void
    {
        $action->execute($this->exam);
    }

    /**
     * Record the room sheets that could not be generated; their download stays "being prepared".
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('Exam room sheets not generated', ['exam' => $this->exam->id, 'error' => $exception?->getMessage()]);
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return ['exam:'.$this->exam->id, 'roster'];
    }
}
