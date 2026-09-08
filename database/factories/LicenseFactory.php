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

        return [
            'id' => (string) Str::uuid7(),
            'client_id' => Client::factory(),
            'license_key' => (string) Str::uuid7(),
            'signing_secret' => Str::random(config('nusalicense.verification.signing_secret_length')),
            'status' => LicenseStatus::Active,
            'issued_at' => now()->subMonths(1),
            'expires_at' => now()->addYear(),
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
            'issued_at' => now()->subYear(),
            'expires_at' => fake()->dateTimeBetween('-6 months', '-1 day'), // eksplisit di masa lalu
        ]);
    }

    public function expiringSoon(): static
    {
        return $this->state(fn () => [
            'status' => LicenseStatus::Active,
            'issued_at' => now()->subMonths(11),
            'expires_at' => fake()->dateTimeBetween('now', '+7 days'),
        ]);
    }
}
