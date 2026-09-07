<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'name' => fake()->name(),
            'company_name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone_number' => '+62'.fake()->numerify('8##########'),
            'pic_name' => fake()->name(),
            'villa_address' => fake()->address(),
            'tier' => fake()->randomElement(['standard', 'premium', 'enterprise']),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
