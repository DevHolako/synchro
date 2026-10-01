<?php

namespace Database\Factories;

use App\Enums\ProgramModality;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'name' => 'Programme '.implode(' ', (array) fake()->unique()->words(2)),
            'code' => 'PRG-'.strtoupper(fake()->unique()->bothify('??##')),
            'program_modality' => fake()->randomElement(ProgramModality::cases()),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    public function formationInitiale(): static
    {
        return $this->state(fn (array $attributes) => [
            'program_modality' => ProgramModality::FormationInitiale,
        ]);
    }

    public function tempsAmenage(): static
    {
        return $this->state(fn (array $attributes) => [
            'program_modality' => ProgramModality::TempsAmenage,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
