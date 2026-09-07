<?php

namespace Database\Factories;

use App\Enums\LicenseStatus;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LicenseFactory extends Factory
{
    public function definition(): array
    {
        $issuedAt = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'id' => (string) Str::uuid7(),
            'client_id' => Client::factory(),
            'license_key' => (string) Str::uuid7(),
            'signing_secret' => Str::random(64),
            'status' => LicenseStatus::Active,
            'issued_at' => $issuedAt,
            'expires_at' => fake()->dateTimeBetween($issuedAt, '+1 year'),
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn () => [
            'status' => LicenseStatus::Suspended,
            'suspend_reason' => fake()->randomElement(['unpaid_invoice', 'contract_ended', 'policy_violation']),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => LicenseStatus::Expired,
            'expires_at' => fake()->dateTimeBetween('-6 months', '-1 day'),
        ]);
    }

    public function expiringSoon(): static
    {
        return $this->state(fn () => [
            'status' => LicenseStatus::Active,
            'expires_at' => fake()->dateTimeBetween('now', '+7 days'),
        ]);
    }
}
