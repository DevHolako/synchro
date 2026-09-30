<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Campus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Building>
 */
class BuildingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campus_id' => Campus::factory(),
            'name' => 'Bâtiment '.fake()->unique()->bothify('?##'),
            'code' => 'BAT-'.fake()->unique()->randomNumber(3),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
