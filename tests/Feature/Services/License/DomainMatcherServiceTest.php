<?php

use App\Models\Client;
use App\Models\Domain;
use App\Models\License;
use App\Services\License\DomainMatcherService;

beforeEach(function () {
    $this->service = new DomainMatcherService;
    $this->client = Client::factory()->create();
    $this->license = License::factory()->for($this->client)->create();
});

it('mengenali domain yang terverifikasi sebagai valid', function () {
    Domain::factory()->for($this->client)->create([
        'domain_name' => 'villa-sunset.com',
        'is_verified' => true,
    ]);

    $result = $this->service->isDomainRegistered($this->license->fresh(), 'villa-sunset.com');

    expect($result)->toBeTrue();
});

it('menolak domain yang belum terverifikasi', function () {
    Domain::factory()->for($this->client)->create([
        'domain_name' => 'villa-sunset.com',
        'is_verified' => false,
    ]);

    $result = $this->service->isDomainRegistered($this->license->fresh(), 'villa-sunset.com');

    expect($result)->toBeFalse();
});

it('menolak domain yang tidak terdaftar sama sekali', function () {
    $result = $this->service->isDomainRegistered($this->license->fresh(), 'domain-asing.com');

    expect($result)->toBeFalse();
});

it('menormalisasi domain dengan www dan protocol sebelum matching', function () {
    Domain::factory()->for($this->client)->create([
        'domain_name' => 'villa-sunset.com',
        'is_verified' => true,
    ]);

    $result = $this->service->isDomainRegistered($this->license->fresh(), 'https://www.villa-sunset.com/');

    expect($result)->toBeTrue();
});

it('mencocokkan IP server dengan benar', function () {
    Domain::factory()->for($this->client)->create([
        'domain_name' => 'villa-sunset.com',
        'server_ip' => '203.0.113.10',
    ]);

    $valid = $this->service->isIpMatching($this->license->fresh(), 'villa-sunset.com', '203.0.113.10');
    $invalid = $this->service->isIpMatching($this->license->fresh(), 'villa-sunset.com', '203.0.113.99');

    expect($valid)->toBeTrue();
    expect($invalid)->toBeFalse();
});

it('tidak block request kalau server_ip belum diisi admin', function () {
    Domain::factory()->for($this->client)->create([
        'domain_name' => 'villa-sunset.com',
        'server_ip' => null,
    ]);

    $result = $this->service->isIpMatching($this->license->fresh(), 'villa-sunset.com', 'ip-apapun');

    expect($result)->toBeTrue();
});
