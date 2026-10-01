<?php

namespace App\Actions\Unavailabilities;

use App\Enums\UnavailabilityStatus;
use App\Enums\UnavailabilityType;
use App\Models\TeacherUnavailability;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeclareUnavailabilityAction
{
    public function __construct(private GuardUnavailabilityOverlapAction $guardOverlap) {}

    /**
     * Submit a pending unavailability for the teacher.
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
     */
    public function execute(User $teacher, array $data): TeacherUnavailability
    {
        return DB::transaction(function () use ($teacher, $data): TeacherUnavailability {
            $unavailability = new TeacherUnavailability([
                ...self::attributes($data),
                'teacher_id' => $teacher->id,
                'status' => UnavailabilityStatus::Pending,
            ]);

            $this->guardOverlap->execute($unavailability);

            $unavailability->save();

            return $unavailability;
        });
    }

    /**
     * Normalize submitted fields: an ad-hoc range has no weekday.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function attributes(array $data): array
    {
        $type = UnavailabilityType::from($data['type']);

        return [
            'type' => $type,
            'day_of_week' => $type === UnavailabilityType::RecurringWeekly ? (int) $data['day_of_week'] : null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'reason' => $data['reason'],
        ];
    }
}
