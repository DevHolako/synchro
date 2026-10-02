<?php

namespace Database\Factories;

use App\Enums\ExamState;
use App\Models\Exam;
use App\Models\ExamPeriod;
use App\Models\Module;
use App\Models\StudentGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Exam>
 */
class ExamFactory extends Factory
{
    /**
     * Define the model's default state: a draft 09:00–11:00 exam next Monday.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = Carbon::parse('next monday 09:00');

        return [
            'exam_period_id' => ExamPeriod::factory(),
            'module_id' => Module::factory(),
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHours(2),
            'state' => ExamState::Draft,
        ];
    }

    /**
     * Link the exam to these groups once created.
     */
    public function forGroups(StudentGroup ...$groups): static
    {
        return $this->afterCreating(
            fn (Exam $exam) => $exam->studentGroups()->attach(array_map(fn (StudentGroup $group) => $group->id, $groups)),
        );
    }

    public function between(string $startsAt, string $endsAt): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => ['state' => ExamState::Scheduled]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => ExamState::Published,
            'published_at' => Carbon::now(),
            'published_by' => User::factory()->coordinator(),
        ]);
    }

    public function completed(): static
    {
        return $this->published()->state(fn (array $attributes) => ['state' => ExamState::Completed]);
    }

    public function archived(): static
    {
        return $this->published()->state(fn (array $attributes) => ['state' => ExamState::Archived]);
    }
}
