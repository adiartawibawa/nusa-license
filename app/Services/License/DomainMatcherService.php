<?php

namespace App\Services\License;

use App\Models\Domain;
use App\Models\License;

class DomainMatcherService
{
    /**
     * Cocokkan domain yang dikirim client dengan daftar domain terdaftar milik client.
     * Strict match — tidak ada wildcard/subdomain fallback kecuali eksplisit didaftarkan.
     */
    public function isDomainRegistered(License $license, string $requestedDomain): bool
    {
        $normalized = $this->normalize($requestedDomain);

        return $license->client->domains()
            ->where('is_verified', true)
            ->where('domain_name', $normalized)
            ->exists();
    }

    public function isIpMatching(License $license, string $requestedDomain, string $requestIp): bool
    {
        $normalized = $this->normalize($requestedDomain);

        $domain = $license->client->domains()
            ->where('domain_name', $normalized)
            ->first();

        if (! $domain || ! $domain->server_ip) {
            // Kalau server_ip belum diisi admin, jangan block — anggap belum wajib
            return true;
        }

        return $domain->server_ip === $requestIp;
    }

    private function normalize(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#^www\.#', '', $domain);

        return rtrim($domain, '/');
    }
}
