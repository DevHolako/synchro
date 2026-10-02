<?php

namespace Database\Factories;

use App\Enums\ExamSessionType;
use App\Models\ExamPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<ExamPeriod>
 */
class ExamPeriodFactory extends Factory
{
    /**
     * Define the model's default state: a normal session from today for eight weeks.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Session '.fake()->unique()->numberBetween(1, 9999),
            'session_type' => ExamSessionType::Normal,
            'academic_year' => '2026-2027',
            'start_date' => Carbon::today()->format('Y-m-d'),
            'end_date' => Carbon::today()->addWeeks(8)->format('Y-m-d'),
        ];
    }

    public function between(string $startDate, string $endDate): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    public function retake(): static
    {
        return $this->state(fn (array $attributes) => [
            'session_type' => ExamSessionType::Rattrapage,
        ]);
    }
}
