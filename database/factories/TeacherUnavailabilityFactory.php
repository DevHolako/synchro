<?php

namespace Database\Factories;

use App\Enums\UnavailabilityStatus;
use App\Enums\UnavailabilityType;
use App\Models\TeacherUnavailability;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeacherUnavailability>
 */
class TeacherUnavailabilityFactory extends Factory
{
    /**
     * Define the model's default state: a pending, open-ended recurring weekly block.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'teacher_id' => User::factory()->teacher(),
            'type' => UnavailabilityType::RecurringWeekly,
            'day_of_week' => fake()->numberBetween(1, 7),
            'start_date' => today(),
            'end_date' => null,
            'start_time' => '18:00',
            'end_time' => '22:00',
            'reason' => fake()->sentence(),
            'status' => UnavailabilityStatus::Pending,
        ];
    }

    public function recurring(int $dayOfWeek, string $startTime, string $endTime): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => UnavailabilityType::RecurringWeekly,
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }

    public function adHoc(string $startDate, string $endDate, ?string $startTime = null, ?string $endTime = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => UnavailabilityType::AdHocDate,
            'day_of_week' => null,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UnavailabilityStatus::Approved,
            'reviewed_by' => User::factory()->coordinator(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UnavailabilityStatus::Rejected,
            'reviewed_by' => User::factory()->coordinator(),
            'reviewed_at' => now(),
            'review_note' => fake()->sentence(),
        ]);
    }
}
