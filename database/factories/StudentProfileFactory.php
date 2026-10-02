<?php

namespace Database\Factories;

use App\Models\StudentGroup;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'student_group_id' => StudentGroup::factory(),
            'student_number' => fake()->unique()->bothify('ETU-######'),
            'phone' => fake()->numerify('06########'),
        ];
    }
}
