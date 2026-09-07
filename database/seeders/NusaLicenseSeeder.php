<?php

namespace Database\Seeders;

use App\Enums\TelemetryResult;
use App\Models\Client;
use App\Models\Domain;
use App\Models\License;
use App\Models\TelemetryLog;
use Illuminate\Database\Seeder;

class NusaLicenseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Client dengan license ACTIVE + domain valid — kasus normal
        Client::factory()
            ->count(5)
            ->has(Domain::factory()->count(1))
            ->has(License::factory())
            ->create()
            ->each(fn (Client $client) => $this->seedTelemetry($client, TelemetryResult::Success));

        // 2. Client dengan license SUSPENDED (kill-switch aktif) — kasus tunggakan
        Client::factory()
            ->count(2)
            ->has(Domain::factory())
            ->has(License::factory()->suspended())
            ->create()
            ->each(fn (Client $client) => $this->seedTelemetry($client, TelemetryResult::RejectedStatus));

        // 3. Client dengan license EXPIRED — belum sempat renew
        Client::factory()
            ->count(2)
            ->has(Domain::factory())
            ->has(License::factory()->expired())
            ->create();

        // 4. Client mendekati expiry (untuk test cron reminder)
        Client::factory()
            ->count(3)
            ->has(Domain::factory())
            ->has(License::factory()->expiringSoon())
            ->create();

        // 5. Kasus UNREGISTERED_DOMAIN — license valid tapi domain belum diverifikasi
        Client::factory()
            ->count(1)
            ->has(Domain::factory()->unverified())
            ->has(License::factory())
            ->create()
            ->each(fn (Client $client) => $this->seedTelemetry($client, TelemetryResult::RejectedDomain));

        // 6. Client non-aktif (soft-deleted candidate, tidak ada license)
        Client::factory()
            ->count(2)
            ->inactive()
            ->create();
    }

    private function seedTelemetry(Client $client, TelemetryResult $result): void
    {
        $license = $client->licenses->first();
        $domain = $client->domains->first();

        if (! $license) {
            return;
        }

        TelemetryLog::factory()
            ->count(fake()->numberBetween(3, 10))
            ->create([
                'license_id' => $license->id,
                'domain_used' => $domain?->domain_name,
                'result' => $result,
            ]);
    }
}
