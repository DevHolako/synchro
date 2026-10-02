<?php

namespace App\Jobs;

use App\Actions\Exams\StoreExamRosterAction;
use App\Models\Exam;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
     * @return list<string>
     */
    public function tags(): array
    {
        return ['exam:'.$this->exam->id, 'roster'];
    }
}
