<?php

namespace App\Services\License;

use App\Enums\LicenseStatus;
use App\Enums\TelemetryResult;
use App\Jobs\ProcessTelemetryPingJob;
use App\Models\License;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LicenseVerificationService
{
    private const STATUS_CACHE_TTL = 60; // detik — cache hasil resolve status

    public function __construct(
        private readonly SignatureService $signatureService,
        private readonly DomainMatcherService $domainMatcher,
    ) {}

    /**
     * Entry point dari API controller.
     * Return array shape siap dikirim sebagai JSON response — response cepat,
     * pencatatan log didorong ke queue (tidak blocking).
     */
    public function verify(array $payload, string $requestIp): array
    {
        $license = License::with('client.domains')
            ->where('license_key', $payload['license_key'])
            ->first();

        if (! $license) {
            // Jangan bocorkan detail — treat sebagai unregistered
            return $this->buildResponse(LicenseStatus::UnregisteredDomain, 'License not found');
        }

        $signatureValid = $this->signatureService->verify(
            licenseKey: $license->license_key,
            domain: $payload['domain'],
            timestamp: $payload['timestamp'],
            nonce: $payload['nonce'],
            signature: $payload['signature'],
            secret: $license->signing_secret,
        );

        if (! $signatureValid) {
            $this->dispatchLog($license, $payload, $requestIp, TelemetryResult::RejectedSignature);

            return $this->buildResponse(LicenseStatus::Suspended, 'Invalid signature');
        }

        $status = $this->resolveStatus($license, $payload['domain'], $requestIp);

        $result = $status === LicenseStatus::Active
            ? TelemetryResult::Success
            : ($status === LicenseStatus::UnregisteredDomain
                ? TelemetryResult::RejectedDomain
                : TelemetryResult::RejectedStatus);

        $this->dispatchLog($license, $payload, $requestIp, $result);

        if ($result === TelemetryResult::Success) {
            // Update last_verified_at tanpa nunggu queue — ringan, cukup 1 kolom
            $license->update(['last_verified_at' => now()]);
        }

        return $this->buildResponse($status, $status->label());
    }

    /**
     * Logic inti resolusi status: dipakai juga oleh Filament Action manual toggle
     * dan scheduled command auto-suspend, supaya satu sumber kebenaran.
     */
    public function resolveStatus(License $license, ?string $domain = null, ?string $ip = null): LicenseStatus
    {
        $cacheKey = "license_status:{$license->license_key}";

        return Cache::remember($cacheKey, self::STATUS_CACHE_TTL, function () use ($license, $domain, $ip) {
            // Manual override admin (suspended) selalu menang — tidak perlu cek lain
            if ($license->status === LicenseStatus::Suspended) {
                return LicenseStatus::Suspended;
            }

            if ($license->isExpired()) {
                return LicenseStatus::Expired;
            }

            if ($domain && ! $this->domainMatcher->isDomainRegistered($license, $domain)) {
                return LicenseStatus::UnregisteredDomain;
            }

            if ($domain && $ip && ! $this->domainMatcher->isIpMatching($license, $domain, $ip)) {
                return LicenseStatus::UnregisteredDomain;
            }

            return LicenseStatus::Active;
        });
    }

    /**
     * Dipanggil dari Filament Action (kill-switch manual) — bypass cache langsung tulis DB.
     */
    public function forceSuspend(License $license, string $reason, ?string $adminId = null): void
    {
        DB::transaction(function () use ($license, $reason, $adminId) {
            $license->update([
                'status' => LicenseStatus::Suspended,
                'suspend_reason' => $reason,
                'changed_by' => $adminId,
            ]);
        });

        Cache::forget("license_status:{$license->license_key}");
    }

    public function reactivate(License $license, ?string $adminId = null): void
    {
        DB::transaction(function () use ($license, $adminId) {
            $license->update([
                'status' => LicenseStatus::Active,
                'suspend_reason' => null,
                'changed_by' => $adminId,
            ]);
        });

        Cache::forget("license_status:{$license->license_key}");
    }

    private function dispatchLog(License $license, array $payload, string $ip, TelemetryResult $result): void
    {
        ProcessTelemetryPingJob::dispatch(
            licenseId: $license->id,
            requestIp: $ip,
            domainUsed: $payload['domain'] ?? null,
            appVersion: $payload['app_version'] ?? null,
            result: $result,
            payloadMeta: $payload,
        );
    }

    private function buildResponse(LicenseStatus $status, string $message): array
    {
        return [
            'status' => $status->value,
            'message' => $message,
            'locked' => $status !== LicenseStatus::Active,
        ];
    }
}
