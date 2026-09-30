<?php

namespace Database\Factories;

use App\Models\Campus;
use App\Models\Program;
use App\Models\StudentGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentGroup>
 */
class StudentGroupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'campus_id' => null,
            'name' => 'Groupe '.fake()->unique()->numberBetween(1, 999),
            'code' => 'GRP-'.strtoupper(fake()->unique()->bothify('??##')),
            'academic_year' => '2026-2027',
            'expected_headcount' => fake()->numberBetween(15, 45),
            'is_active' => true,
        ];
    }

    public function withCampus(?Campus $campus = null): static
    {
        return $this->state(fn (array $attributes) => [
            'campus_id' => $campus?->id ?? Campus::factory(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
