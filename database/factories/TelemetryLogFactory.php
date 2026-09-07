<?php

namespace Database\Factories;

use App\Enums\TelemetryResult;
use App\Models\License;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TelemetryLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'license_id' => License::factory(),
            'request_ip' => fake()->ipv4(),
            'domain_used' => fake()->domainName(),
            'app_version' => fake()->randomElement(['1.0.0', '1.2.0', '2.0.1']),
            'result' => TelemetryResult::Success,
            'payload_meta' => ['source' => 'seeder'],
            'pinged_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
