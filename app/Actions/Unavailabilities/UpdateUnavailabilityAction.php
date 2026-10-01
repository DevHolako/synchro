<?php

namespace App\Actions\Unavailabilities;

use App\Models\TeacherUnavailability;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateUnavailabilityAction
{
    public function __construct(private GuardUnavailabilityOverlapAction $guardOverlap) {}

    /**
     * Edit a pending unavailability. Reviewed ones are locked: the teacher deletes and resubmits.
     *
     * @param array{
     *     type: string,
     *     day_of_week?: int|null,
     *     start_date: string,
     *     end_date?: string|null,
     *     start_time?: string|null,
     *     end_time?: string|null,
     *     reason: string
     * } $data
     *
     * @throws ValidationException
     */
    public function execute(TeacherUnavailability $unavailability, array $data): TeacherUnavailability
    {
        return DB::transaction(function () use ($unavailability, $data): TeacherUnavailability {
            $unavailability = TeacherUnavailability::query()->lockForUpdate()->findOrFail($unavailability->id);

            if (! $unavailability->isPending()) {
                throw ValidationException::withMessages([
                    'status' => __('messages.unavailability_not_editable'),
                ]);
            }

            $unavailability->fill(DeclareUnavailabilityAction::attributes($data));

            $this->guardOverlap->execute($unavailability);

            $unavailability->save();

            return $unavailability;
        });
    }
}
