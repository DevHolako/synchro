<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $courseCapacity = fake()->numberBetween(20, 80);
        $examCapacity = (int) floor($courseCapacity / 2);

        return [
            'building_id' => Building::factory(),
            'name' => 'Salle '.fake()->unique()->bothify('###?'),
            'code' => 'R-'.fake()->unique()->bothify('###?'),
            'floor' => fake()->numberBetween(0, 3),
            'course_capacity' => $courseCapacity,
            'exam_capacity' => max(1, $examCapacity),
            'has_projector' => fake()->boolean(70),
            'is_lab' => false,
            'has_computers' => false,
            'has_sound_system' => fake()->boolean(40),
            'is_active' => true,
        ];
    }

    public function lab(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Labo Info '.fake()->unique()->numberBetween(1, 99),
            'is_lab' => true,
            'has_computers' => true,
            'has_projector' => true,
        ]);
    }

    public function amphitheater(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Amphithéâtre '.fake()->unique()->numberBetween(1, 10),
            'course_capacity' => 150,
            'exam_capacity' => 75,
            'has_projector' => true,
            'has_sound_system' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
