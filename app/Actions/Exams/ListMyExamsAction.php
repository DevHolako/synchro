<?php

namespace App\Actions\Exams;

use App\Enums\Permission;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;

class ListMyExamsAction
{
    /**
     * @return Collection<int, Exam>
     *
     * @throws AuthorizationException
     */
    public function execute(User $user): Collection
    {
        if (! $user->hasPermission(Permission::ViewExams)) {
            throw new AuthorizationException;
        }

        return Exam::query()
            ->concerning($user)
            ->with([
                'module:id,code,name,color_code,program_id',
                'examPeriod:id,name,session_type',
                'roomAssignments.room:id,name,code,building_id',
                'roomAssignments.room.building:id,name',
                'candidates' => fn ($candidates) => $candidates->where('student_id', $user->id)->with('roomAssignment.room:id,name,code'),
            ])
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();
    }
}
