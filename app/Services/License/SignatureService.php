<?php

namespace App\Services\License;

use Illuminate\Support\Facades\Cache;

class SignatureService
{
    public function verify(
        string $licenseKey,
        string $domain,
        int $timestamp,
        string $nonce,
        string $signature,
        string $secret,
    ): bool {
        if (! $this->isTimestampFresh($timestamp)) {
            return false;
        }

        if ($this->isNonceReused($nonce, $licenseKey)) {
            return false;
        }

        $expected = $this->sign($licenseKey, $domain, $timestamp, $nonce, $secret);

        if (! hash_equals($expected, $signature)) {
            return false;
        }

        $this->markNonceUsed($nonce, $licenseKey);

        return true;
    }

    public function sign(
        string $licenseKey,
        string $domain,
        int $timestamp,
        string $nonce,
        string $secret,
    ): string {
        $payload = implode('|', [$licenseKey, $domain, $timestamp, $nonce]);

        return hash_hmac('sha256', $payload, $secret);
    }

    private function isTimestampFresh(int $timestamp): bool
    {
        $maxDrift = config('nusalicense.verification.max_timestamp_drift_seconds');

        return abs(time() - $timestamp) <= $maxDrift;
    }

    private function isNonceReused(string $nonce, string $licenseKey): bool
    {
        return Cache::has($this->nonceCacheKey($nonce, $licenseKey));
    }

    private function markNonceUsed(string $nonce, string $licenseKey): void
    {
        $ttl = config('nusalicense.verification.nonce_ttl_seconds');

        Cache::put($this->nonceCacheKey($nonce, $licenseKey), true, $ttl);
    }

    private function nonceCacheKey(string $nonce, string $licenseKey): string
    {
        return "license_nonce:{$licenseKey}:{$nonce}";
    }
}
