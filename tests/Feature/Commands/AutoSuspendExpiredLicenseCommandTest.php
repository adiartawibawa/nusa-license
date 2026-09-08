<?php

use App\Enums\LicenseStatus;
use App\Models\Client;
use App\Models\License;
use App\Notifications\LicenseExpiredNotice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->client = Client::factory()->create();
});

it('mengubah license ACTIVE yang sudah lewat expires_at menjadi EXPIRED', function () {
    Notification::fake();

    $license = License::factory()->for($this->client)->create([
        'status' => LicenseStatus::Active,
        'expires_at' => now()->subDays(5),
        'grace_period_until' => null,
    ]);

    $this->artisan('license:auto-expire')
        ->assertSuccessful();

    expect($license->fresh()->status)->toBe(LicenseStatus::Expired);
});

it('tidak mengubah license yang masih dalam grace period', function () {
    $license = License::factory()->for($this->client)->create([
        'status' => LicenseStatus::Active,
        'expires_at' => now()->subDays(1),
        'grace_period_until' => now()->addDays(2),
    ]);

    $this->artisan('license:auto-expire')
        ->assertSuccessful();

    expect($license->fresh()->status)->toBe(LicenseStatus::Active);
});

it('mengubah license setelah grace period benar-benar lewat', function () {
    $license = License::factory()->for($this->client)->create([
        'status' => LicenseStatus::Active,
        'expires_at' => now()->subDays(10),
        'grace_period_until' => now()->subDays(1),
    ]);

    $this->artisan('license:auto-expire')
        ->assertSuccessful();

    expect($license->fresh()->status)->toBe(LicenseStatus::Expired);
});

it('tidak menyentuh license yang statusnya sudah SUSPENDED (manual override tetap menang)', function () {
    $license = License::factory()->suspended()->for($this->client)->create([
        'expires_at' => now()->subDays(5),
    ]);

    $this->artisan('license:auto-expire')
        ->assertSuccessful();

    expect($license->fresh()->status)->toBe(LicenseStatus::Suspended);
});

it('tidak menyentuh license yang sudah EXPIRED sebelumnya', function () {
    $license = License::factory()->expired()->for($this->client)->create();
    $originalChangedAt = $license->last_status_changed_at;

    $this->artisan('license:auto-expire')
        ->assertSuccessful();

    expect($license->fresh()->last_status_changed_at)
        ->toEqual($originalChangedAt);
});

it('tidak mengubah apapun ketika --dry-run diaktifkan', function () {
    $license = License::factory()->for($this->client)->create([
        'status' => LicenseStatus::Active,
        'expires_at' => now()->subDays(5),
    ]);

    $this->artisan('license:auto-expire', ['--dry-run' => true])
        ->assertSuccessful();

    expect($license->fresh()->status)->toBe(LicenseStatus::Active);
});

it('menghapus cache status license yang diubah', function () {
    $license = License::factory()->for($this->client)->create([
        'status' => LicenseStatus::Active,
        'expires_at' => now()->subDays(5),
    ]);

    Cache::put("license_status:{$license->license_key}", LicenseStatus::Active, 60);

    $this->artisan('license:auto-expire')->assertSuccessful();

    expect(Cache::has("license_status:{$license->license_key}"))->toBeFalse();
});

it('menghapus cache widget dashboard setelah ada license yang diproses', function () {
    License::factory()->for($this->client)->create([
        'status' => LicenseStatus::Active,
        'expires_at' => now()->subDays(5),
    ]);

    Cache::put('widget:license_status_counts', ['active' => 10], 60);

    $this->artisan('license:auto-expire')->assertSuccessful();

    expect(Cache::has('widget:license_status_counts'))->toBeFalse();
});

it('mengirim notifikasi ke client untuk setiap license yang di-expire', function () {
    Notification::fake();

    License::factory()->for($this->client)->create([
        'status' => LicenseStatus::Active,
        'expires_at' => now()->subDays(5),
    ]);

    $this->artisan('license:auto-expire')->assertSuccessful();

    Notification::assertSentTo(
        $this->client,
        LicenseExpiredNotice::class,
    );
});

it('memproses banyak license expired sekaligus tanpa error', function () {
    Notification::fake();

    License::factory()->count(5)->for($this->client)->create([
        'status' => LicenseStatus::Active,
        'expires_at' => now()->subDays(3),
    ]);

    $this->artisan('license:auto-expire')->assertSuccessful();

    expect(License::where('status', LicenseStatus::Expired)->count())->toBe(5);
});

it('tidak melakukan apa-apa ketika tidak ada license yang perlu di-expire', function () {
    License::factory()->for($this->client)->create([
        'status' => LicenseStatus::Active,
        'expires_at' => now()->addYear(),
    ]);

    $this->artisan('license:auto-expire')
        ->expectsOutput('Tidak ada license yang perlu di-expire.')
        ->assertSuccessful();
});
