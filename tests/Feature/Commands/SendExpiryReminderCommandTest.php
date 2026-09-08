<?php

use App\Enums\LicenseStatus;
use App\Models\AlertLog;
use App\Models\Client;
use App\Models\License;
use App\Notifications\LicenseExpiringSoon;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->client = Client::factory()->create([
        'phone_number' => '+6281234567890',
    ]);
});

it('mengirim reminder untuk license yang expire tepat H-7', function () {
    Notification::fake();

    $license = License::factory()->for($this->client)->create([
        'status' => LicenseStatus::Active,
        'expires_at' => now()->addDays(7),
    ]);

    $this->artisan('license:send-expiry-reminder')->assertSuccessful();

    Notification::assertSentTo($this->client, LicenseExpiringSoon::class);

    expect(AlertLog::where('license_id', $license->id)
        ->where('milestone_days', 7)
        ->where('status', 'sent')
        ->exists()
    )->toBeTrue();
});

it('mengirim reminder untuk milestone H-3 dan H-1', function () {
    Notification::fake();

    $licenseH3 = License::factory()->for($this->client)->create([
        'expires_at' => now()->addDays(3),
    ]);
    $licenseH1 = License::factory()->for($this->client)->create([
        'expires_at' => now()->addDays(1),
    ]);

    $this->artisan('license:send-expiry-reminder')->assertSuccessful();

    expect(AlertLog::where('license_id', $licenseH3->id)->where('milestone_days', 3)->exists())->toBeTrue();
    expect(AlertLog::where('license_id', $licenseH1->id)->where('milestone_days', 1)->exists())->toBeTrue();
});

it('tidak mengirim reminder untuk license yang expire di luar milestone (misal H-5)', function () {
    Notification::fake();

    License::factory()->for($this->client)->create([
        'expires_at' => now()->addDays(5),
    ]);

    $this->artisan('license:send-expiry-reminder')->assertSuccessful();

    Notification::assertNothingSent();
});

it('tidak mengirim reminder untuk license yang sudah SUSPENDED', function () {
    Notification::fake();

    License::factory()->suspended()->for($this->client)->create([
        'expires_at' => now()->addDays(7),
    ]);

    $this->artisan('license:send-expiry-reminder')->assertSuccessful();

    Notification::assertNothingSent();
});

it('membuat AlertLog terpisah untuk channel email dan whatsapp', function () {
    Notification::fake();

    $license = License::factory()->for($this->client)->create([
        'expires_at' => now()->addDays(7),
    ]);

    $this->artisan('license:send-expiry-reminder')->assertSuccessful();

    $logs = AlertLog::where('license_id', $license->id)->get();

    expect($logs->pluck('channel')->sort()->values()->all())->toBe(['email', 'whatsapp']);
});

it('tidak membuat AlertLog channel whatsapp kalau client tidak punya nomor telepon', function () {
    Notification::fake();

    $clientTanpaWA = Client::factory()->create(['phone_number' => null]);
    $license = License::factory()->for($clientTanpaWA)->create([
        'expires_at' => now()->addDays(7),
    ]);

    $this->artisan('license:send-expiry-reminder')->assertSuccessful();

    $logs = AlertLog::where('license_id', $license->id)->get();

    expect($logs->pluck('channel')->all())->toBe(['email']);
});

it('tidak mengirim reminder duplikat untuk milestone yang sama di hari yang sama', function () {
    Notification::fake();

    $license = License::factory()->for($this->client)->create([
        'expires_at' => now()->addDays(7),
    ]);

    $this->artisan('license:send-expiry-reminder')->assertSuccessful();
    $firstRunCount = AlertLog::where('license_id', $license->id)->count();

    // Jalankan command kedua kali di hari yang sama — harus di-skip, tidak dobel
    $this->artisan('license:send-expiry-reminder')->assertSuccessful();
    $secondRunCount = AlertLog::where('license_id', $license->id)->count();

    expect($secondRunCount)->toBe($firstRunCount);
});

it('mencatat status failed di AlertLog ketika notifikasi gagal terkirim', function () {
    Notification::shouldReceive('send')->andThrow(new RuntimeException('SMTP down'));

    $license = License::factory()->for($this->client)->create([
        'expires_at' => now()->addDays(7),
    ]);

    $this->artisan('license:send-expiry-reminder');

    expect(AlertLog::where('license_id', $license->id)
        ->where('status', 'failed')
        ->exists()
    )->toBeTrue();
});

it('tidak membuat AlertLog apapun ketika --dry-run diaktifkan', function () {
    Notification::fake();

    License::factory()->for($this->client)->create([
        'expires_at' => now()->addDays(7),
    ]);

    $this->artisan('license:send-expiry-reminder', ['--dry-run' => true])->assertSuccessful();

    expect(AlertLog::count())->toBe(0);
    Notification::assertNothingSent();
});

it('mengirim reminder terpisah untuk beberapa client dengan milestone berbeda dalam satu run', function () {
    Notification::fake();

    $clientA = Client::factory()->create(['phone_number' => null]);
    $clientB = Client::factory()->create(['phone_number' => null]);

    License::factory()->for($clientA)->create(['expires_at' => now()->addDays(7)]);
    License::factory()->for($clientB)->create(['expires_at' => now()->addDays(1)]);

    $this->artisan('license:send-expiry-reminder')->assertSuccessful();

    Notification::assertSentTo($clientA, LicenseExpiringSoon::class);
    Notification::assertSentTo($clientB, LicenseExpiringSoon::class);
});
