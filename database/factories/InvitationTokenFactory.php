<?php

namespace Database\Factories;

use App\Models\InvitationToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InvitationToken>
 */
class InvitationTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->invited(),
            'invited_by' => null,
            'token_hash' => InvitationToken::hashToken(Str::random(64)),
            'expires_at' => now()->addHours(InvitationToken::LIFETIME_HOURS),
        ];
    }

    /**
     * Indicate that the invitation window has elapsed.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subHour(),
        ]);
    }

    /**
     * Indicate that the invitation has already been used.
     */
    public function consumed(): static
    {
        return $this->state(fn (array $attributes) => [
            'consumed_at' => now(),
        ]);
    }
}
