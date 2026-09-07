<?php

namespace App\Services\License;

use Illuminate\Support\Facades\Cache;

class SignatureService
{
    private const MAX_TIMESTAMP_DRIFT = 300; // 5 menit toleransi clock skew

    private const NONCE_TTL = 600;            // simpan nonce 10 menit utk cegah replay

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

        // hash_equals wajib, hindari timing attack
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
        return abs(time() - $timestamp) <= self::MAX_TIMESTAMP_DRIFT;
    }

    private function isNonceReused(string $nonce, string $licenseKey): bool
    {
        return Cache::has($this->nonceCacheKey($nonce, $licenseKey));
    }

    private function markNonceUsed(string $nonce, string $licenseKey): void
    {
        Cache::put($this->nonceCacheKey($nonce, $licenseKey), true, self::NONCE_TTL);
    }

    private function nonceCacheKey(string $nonce, string $licenseKey): string
    {
        return "license_nonce:{$licenseKey}:{$nonce}";
    }
}
