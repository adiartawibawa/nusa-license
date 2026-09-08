<?php

use App\Enums\LicenseStatus;
use App\Jobs\ProcessTelemetryPingJob;
use App\Models\Client;
use App\Models\Domain;
use App\Models\License;
use App\Services\License\LicenseVerificationService;
use App\Services\License\SignatureService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->service = app(LicenseVerificationService::class);
    $this->client = Client::factory()->create();
});

// --- resolveStatus() ---

it('resolve ACTIVE untuk license normal dengan domain terverifikasi', function () {
    $license = License::factory()->for($this->client)->create();
    $domain = Domain::factory()->for($this->client)->create(['is_verified' => true]);

    $status = $this->service->resolveStatus($license, $domain->domain_name);

    expect($status)->toBe(LicenseStatus::Active);
});

it('resolve SUSPENDED tanpa peduli kondisi lain (manual override selalu menang)', function () {
    $license = License::factory()->suspended()->for($this->client)->create();
    $domain = Domain::factory()->for($this->client)->create(['is_verified' => true]);

    $status = $this->service->resolveStatus($license, $domain->domain_name);

    expect($status)->toBe(LicenseStatus::Suspended);
});

it('resolve EXPIRED ketika sudah lewat expires_at dan tidak ada grace period', function () {
    $license = License::factory()->for($this->client)->create([
        'expires_at' => now()->subDays(5),
        'grace_period_until' => null,
    ]);

    $status = $this->service->resolveStatus($license);

    expect($status)->toBe(LicenseStatus::Expired);
});

it('tetap ACTIVE ketika lewat expires_at tapi masih dalam grace period', function () {
    $license = License::factory()->for($this->client)->create([
        'expires_at' => now()->subDays(1),
        'grace_period_until' => now()->addDays(2),
    ]);
    $domain = Domain::factory()->for($this->client)->create(['is_verified' => true]);

    $status = $this->service->resolveStatus($license, $domain->domain_name);

    expect($status)->toBe(LicenseStatus::Active);
});

it('resolve UNREGISTERED_DOMAIN ketika domain tidak terverifikasi', function () {
    $license = License::factory()->for($this->client)->create();
    Domain::factory()->for($this->client)->create([
        'domain_name' => 'villa-belum-verified.com',
        'is_verified' => false,
    ]);

    $status = $this->service->resolveStatus($license, 'villa-belum-verified.com');

    expect($status)->toBe(LicenseStatus::UnregisteredDomain);
});

it('resolve UNREGISTERED_DOMAIN ketika IP tidak cocok', function () {
    $license = License::factory()->for($this->client)->create();
    $domain = Domain::factory()->for($this->client)->create([
        'is_verified' => true,
        'server_ip' => '203.0.113.10',
    ]);

    $status = $this->service->resolveStatus($license, $domain->domain_name, '203.0.113.99');

    expect($status)->toBe(LicenseStatus::UnregisteredDomain);
});

it('hasil resolveStatus di-cache sehingga query kedua tidak hit database', function () {
    $license = License::factory()->for($this->client)->create();
    Domain::factory()->for($this->client)->create(['is_verified' => true]);

    $first = $this->service->resolveStatus($license, null);

    // Ubah status langsung di DB tanpa lewat service (bypass cache invalidation)
    $license->update(['status' => LicenseStatus::Suspended]);

    $second = $this->service->resolveStatus($license->fresh(), null);

    // Harus tetap ACTIVE karena masih baca dari cache, belum expired TTL
    expect($first)->toBe(LicenseStatus::Active);
    expect($second)->toBe(LicenseStatus::Active);
});

// --- forceSuspend() & reactivate() ---

it('forceSuspend mengubah status dan menghapus cache', function () {
    $license = License::factory()->for($this->client)->create();

    // Warm up cache dulu
    $this->service->resolveStatus($license);
    expect(Cache::has("license_status:{$license->license_key}"))->toBeTrue();

    $this->service->forceSuspend($license, 'unpaid_invoice');

    expect($license->fresh()->status)->toBe(LicenseStatus::Suspended);
    expect($license->fresh()->suspend_reason)->toBe('unpaid_invoice');
    expect(Cache::has("license_status:{$license->license_key}"))->toBeFalse();
});

it('forceSuspend menghapus cache widget dashboard', function () {
    $license = License::factory()->for($this->client)->create();

    Cache::put('widget:license_status_counts', ['active' => 5], 60);

    $this->service->forceSuspend($license, 'policy_violation');

    expect(Cache::has('widget:license_status_counts'))->toBeFalse();
});

it('reactivate mengembalikan status ke ACTIVE dan reset suspend_reason', function () {
    $license = License::factory()->suspended()->for($this->client)->create();

    $this->service->reactivate($license);

    expect($license->fresh()->status)->toBe(LicenseStatus::Active);
    expect($license->fresh()->suspend_reason)->toBeNull();
});

it('resolveStatus langsung reflect perubahan setelah forceSuspend (cache tidak stale)', function () {
    $license = License::factory()->for($this->client)->create();
    Domain::factory()->for($this->client)->create(['is_verified' => true]);

    // Warm up cache jadi ACTIVE
    expect($this->service->resolveStatus($license))->toBe(LicenseStatus::Active);

    $this->service->forceSuspend($license, 'manual_other');

    // Harus langsung SUSPENDED, bukan nunggu TTL cache habis
    expect($this->service->resolveStatus($license->fresh()))->toBe(LicenseStatus::Suspended);
});

// --- verify() end-to-end ---

it('verify() berhasil untuk request yang sah sepenuhnya', function () {
    $license = License::factory()->for($this->client)->create();
    $domain = Domain::factory()->for($this->client)->create([
        'is_verified' => true,
        'server_ip' => null, // <-- tambahkan, supaya IP matching di-skip (lihat DomainMatcherService::isIpMatching)
    ]);

    $signatureService = app(SignatureService::class);
    $timestamp = time();
    $nonce = 'nonce-valid';

    $signature = $signatureService->sign(
        $license->license_key,
        $domain->domain_name,
        $timestamp,
        $nonce,
        $license->signing_secret,
    );

    $result = $this->service->verify([
        'license_key' => $license->license_key,
        'domain' => $domain->domain_name,
        'timestamp' => $timestamp,
        'nonce' => $nonce,
        'signature' => $signature,
    ], '203.0.113.1');

    expect($result['status'])->toBe('active');
    expect($result['locked'])->toBeFalse();
});

it('verify() menolak license_key yang tidak ada', function () {
    $result = $this->service->verify([
        'license_key' => (string) Str::uuid7(),
        'domain' => 'villa-asing.com',
        'timestamp' => time(),
        'nonce' => 'abc',
        'signature' => 'invalid',
    ], '203.0.113.1');

    expect($result['status'])->toBe('unregistered_domain');
    expect($result['locked'])->toBeTrue();
});

it('verify() menolak signature yang tidak valid', function () {
    $license = License::factory()->for($this->client)->create();
    Domain::factory()->for($this->client)->create(['is_verified' => true]);

    $result = $this->service->verify([
        'license_key' => $license->license_key,
        'domain' => 'villa.com',
        'timestamp' => time(),
        'nonce' => 'abc',
        'signature' => 'signature-ngasal',
    ], '203.0.113.1');

    expect($result['status'])->toBe('suspended');
    expect($result['locked'])->toBeTrue();
});

it('verify() update last_verified_at hanya ketika status ACTIVE', function () {
    $license = License::factory()->for($this->client)->create(['last_verified_at' => null]);
    $domain = Domain::factory()->for($this->client)->create([
        'is_verified' => true,
        'server_ip' => null,
    ]);

    $signatureService = app(SignatureService::class);
    $timestamp = time();
    $nonce = 'nonce-xyz';

    $signature = $signatureService->sign(
        $license->license_key, $domain->domain_name, $timestamp, $nonce, $license->signing_secret,
    );

    $this->service->verify([
        'license_key' => $license->license_key,
        'domain' => $domain->domain_name,
        'timestamp' => $timestamp,
        'nonce' => $nonce,
        'signature' => $signature,
    ], '203.0.113.1');

    expect($license->fresh()->last_verified_at)->not->toBeNull();
});

it('verify() mendispatch job pencatatan telemetry log', function () {
    Queue::fake();

    $license = License::factory()->for($this->client)->create();
    Domain::factory()->for($this->client)->create(['is_verified' => true]);

    $this->service->verify([
        'license_key' => $license->license_key,
        'domain' => 'domain-tidak-cocok.com',
        'timestamp' => time(),
        'nonce' => 'abc',
        'signature' => 'invalid-sig',
    ], '203.0.113.1');

    Queue::assertPushed(ProcessTelemetryPingJob::class);
});
