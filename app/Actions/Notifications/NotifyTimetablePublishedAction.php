<?php

namespace App\Actions\Notifications;

use App\Models\StudentGroup;
use App\Models\User;
use App\Notifications\TimetablePublishedNotification;
use Illuminate\Support\Facades\DB;

class NotifyTimetablePublishedAction
{
    /**
     * @param  list<int>  $studentGroupIds
     */
    public function execute(array $studentGroupIds, string $periodDescription): void
    {
        DB::afterCommit(function () use ($studentGroupIds, $periodDescription): void {
            $groups = StudentGroup::query()->whereIn('id', $studentGroupIds)->get();

            foreach ($groups as $group) {
                $students = User::query()
                    ->whereHas('studentProfile', fn ($q) => $q->where('student_group_id', $group->id))
                    ->get();

                foreach ($students as $student) {
                    $student->notify(new TimetablePublishedNotification($group->name, $periodDescription));
                }
            }
        });
    }
}
