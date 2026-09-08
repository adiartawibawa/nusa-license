<?php

use App\Services\License\SignatureService;

beforeEach(function () {
    $this->service = new SignatureService;
    $this->secret = 'test-secret-key-1234567890';
});

it('menghasilkan signature yang valid dan bisa diverifikasi', function () {
    $licenseKey = 'lk-123';
    $domain = 'villa-test.com';
    $timestamp = time();
    $nonce = 'abc123';

    $signature = $this->service->sign($licenseKey, $domain, $timestamp, $nonce, $this->secret);

    $result = $this->service->verify($licenseKey, $domain, $timestamp, $nonce, $signature, $this->secret);

    expect($result)->toBeTrue();
});

it('menolak signature yang salah', function () {
    $result = $this->service->verify(
        licenseKey: 'lk-123',
        domain: 'villa-test.com',
        timestamp: time(),
        nonce: 'abc123',
        signature: 'signature-palsu',
        secret: $this->secret,
    );

    expect($result)->toBeFalse();
});

it('menolak signature yang valid tapi secret berbeda', function () {
    $timestamp = time();
    $nonce = 'abc123';

    $signature = $this->service->sign('lk-123', 'villa-test.com', $timestamp, $nonce, $this->secret);

    $result = $this->service->verify(
        licenseKey: 'lk-123',
        domain: 'villa-test.com',
        timestamp: $timestamp,
        nonce: $nonce,
        signature: $signature,
        secret: 'secret-yang-berbeda',
    );

    expect($result)->toBeFalse();
});

it('menolak request dengan timestamp terlalu lama (di luar drift tolerance)', function () {
    config(['nusalicense.verification.max_timestamp_drift_seconds' => 300]);

    $expiredTimestamp = time() - 400; // 400 detik lalu, melebihi toleransi 300
    $nonce = 'abc123';

    $signature = $this->service->sign('lk-123', 'villa-test.com', $expiredTimestamp, $nonce, $this->secret);

    $result = $this->service->verify(
        licenseKey: 'lk-123',
        domain: 'villa-test.com',
        timestamp: $expiredTimestamp,
        nonce: $nonce,
        signature: $signature,
        secret: $this->secret,
    );

    expect($result)->toBeFalse();
});

it('menerima timestamp dalam batas toleransi drift', function () {
    config(['nusalicense.verification.max_timestamp_drift_seconds' => 300]);

    $timestamp = time() - 200; // masih dalam toleransi 300 detik
    $nonce = 'abc123';

    $signature = $this->service->sign('lk-123', 'villa-test.com', $timestamp, $nonce, $this->secret);

    $result = $this->service->verify(
        licenseKey: 'lk-123',
        domain: 'villa-test.com',
        timestamp: $timestamp,
        nonce: $nonce,
        signature: $signature,
        secret: $this->secret,
    );

    expect($result)->toBeTrue();
});

it('menolak nonce yang dipakai ulang (replay attack)', function () {
    $timestamp = time();
    $nonce = 'nonce-sekali-pakai';

    $signature = $this->service->sign('lk-123', 'villa-test.com', $timestamp, $nonce, $this->secret);

    // Percobaan pertama — harus lolos
    $firstAttempt = $this->service->verify('lk-123', 'villa-test.com', $timestamp, $nonce, $signature, $this->secret);
    expect($firstAttempt)->toBeTrue();

    // Percobaan kedua dengan nonce & signature SAMA — harus ditolak (replay)
    $secondAttempt = $this->service->verify('lk-123', 'villa-test.com', $timestamp, $nonce, $signature, $this->secret);
    expect($secondAttempt)->toBeFalse();
});

it('nonce yang sama boleh dipakai lagi untuk license_key berbeda', function () {
    $timestamp = time();
    $nonce = 'shared-nonce';

    $sig1 = $this->service->sign('lk-AAA', 'villa-a.com', $timestamp, $nonce, $this->secret);
    $sig2 = $this->service->sign('lk-BBB', 'villa-b.com', $timestamp, $nonce, $this->secret);

    expect($this->service->verify('lk-AAA', 'villa-a.com', $timestamp, $nonce, $sig1, $this->secret))->toBeTrue();
    expect($this->service->verify('lk-BBB', 'villa-b.com', $timestamp, $nonce, $sig2, $this->secret))->toBeTrue();
});
