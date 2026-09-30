<?php

namespace Database\Factories;

use App\Models\Campus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campus>
 */
class CampusFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $city = fake()->unique()->city();

        return [
            'name' => 'Campus '.$city,
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'address' => fake()->streetAddress(),
            'city' => $city,
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
