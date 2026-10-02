<?php

namespace Database\Factories;

use App\Models\CourseSession;
use App\Models\Module;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<CourseSession>
 */
class CourseSessionFactory extends Factory
{
    /**
     * Define the model's default state: a 10:00–12:00 session next Monday.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = Carbon::parse('next monday 10:00');

        return [
            'module_id' => Module::factory(),
            'teacher_id' => User::factory()->teacher(),
            'room_id' => Room::factory(),
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHours(2),
        ];
    }

    /**
     * Link the session to these groups once created.
     */
    public function forGroups(StudentGroup ...$groups): static
    {
        return $this->afterCreating(
            fn (CourseSession $session) => $session->studentGroups()->attach(array_map(fn (StudentGroup $group) => $group->id, $groups)),
        );
    }

    public function between(string $startsAt, string $endsAt): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);
    }
}
