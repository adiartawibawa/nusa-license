<?php

namespace Database\Factories;

use App\Models\License;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AlertLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'license_id' => License::factory(),
            'channel' => fake()->randomElement(['whatsapp', 'email']),
            'type' => 'expiry_reminder',
            'milestone_days' => fake()->randomElement([7, 3, 1]),
            'status' => 'sent',
            'error_message' => null,
            'sent_at' => now(),
        ];
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'sent_at' => null,
            'error_message' => fake()->randomElement([
                'Connection timeout to WhatsApp API gateway',
                'Invalid phone number format',
                'SMTP connection refused',
                'WhatsApp API request failed: 401',
            ]),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => 'pending',
            'sent_at' => null,
            'error_message' => null,
        ]);
    }

    public function suspendedNotice(): static
    {
        return $this->state(fn () => [
            'type' => 'suspended_notice',
            'milestone_days' => null,
        ]);
    }

    public function expiredNotice(): static
    {
        return $this->state(fn () => [
            'type' => 'expired_notice',
            'milestone_days' => null,
        ]);
    }
}
