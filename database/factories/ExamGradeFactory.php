<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\ExamGrade;
use App\Models\User;
use App\Support\GradeScale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamGrade>
 */
class ExamGradeFactory extends Factory
{
    /**
     * Define the model's default state: a complete, present line with its final graded 100% on the exam.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $grade = GradeScale::fromHundredths(fake()->numberBetween(0, GradeScale::MAX * 100));

        return [
            'exam_id' => Exam::factory(),
            'student_id' => User::factory()->student(),
            'continuous_assessment_grade' => null,
            'exam_grade' => $grade,
            'final_grade' => $grade,
            'is_absent' => false,
            'remarks' => null,
        ];
    }

    /**
     * A line whose stored final is the one given.
     */
    public function withFinal(string $final): static
    {
        return $this->state(fn (array $attributes) => ['exam_grade' => $final, 'final_grade' => $final]);
    }
}
