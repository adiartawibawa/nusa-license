<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DomainFactory extends Factory
{
    public function definition(): array
    {
        $slug = fake()->unique()->domainWord();

        return [
            'id' => (string) Str::uuid7(),
            'client_id' => Client::factory(),
            'domain_name' => "{$slug}-app.com",
            'server_ip' => fake()->ipv4(),
            'is_primary' => true,
            'is_verified' => true,
            'verified_at' => now(),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => [
            'is_verified' => false,
            'verified_at' => null,
        ]);
    }
}
