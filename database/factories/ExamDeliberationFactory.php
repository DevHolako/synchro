<?php

namespace Database\Factories;

use App\Enums\GradeSheetStatus;
use App\Models\Exam;
use App\Models\ExamDeliberation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamDeliberation>
 */
class ExamDeliberationFactory extends Factory
{
    /**
     * Define the model's default state: a draft grade sheet.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'status' => GradeSheetStatus::Draft,
        ];
    }

    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GradeSheetStatus::Locked,
            'locked_at' => now(),
            'locked_by' => User::factory()->coordinator(),
            'continuous_assessment_weight' => 0,
        ]);
    }
}
