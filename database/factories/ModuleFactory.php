<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lecture = fake()->randomElement([15, 20, 24, 30]);
        $tp = fake()->randomElement([10, 15, 16, 20]);
        $total = $lecture + $tp;

        $colors = ['#3B82F6', '#10B981', '#8B5CF6', '#EC4899', '#F59E0B', '#6366F1', '#06B6D4', '#14B8A6'];

        return [
            'program_id' => Program::factory(),
            'teacher_id' => null,
            'name' => 'Module '.implode(' ', (array) fake()->unique()->words(2)),
            'code' => 'MOD-'.strtoupper(fake()->unique()->bothify('??###')),
            'total_hours' => $total,
            'lecture_hours' => $lecture,
            'tp_hours' => $tp,
            'color_code' => fake()->randomElement($colors),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    public function withTeacher(?User $teacher = null): static
    {
        return $this->state(fn (array $attributes) => [
            'teacher_id' => $teacher->id ?? User::factory()->teacher(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
