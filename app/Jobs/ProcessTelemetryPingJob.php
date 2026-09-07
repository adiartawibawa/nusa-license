<?php

namespace App\Jobs;

use App\Enums\TelemetryResult;
use App\Models\TelemetryLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessTelemetryPingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $licenseId,
        public readonly string $requestIp,
        public readonly ?string $domainUsed,
        public readonly ?string $appVersion,
        public readonly TelemetryResult $result,
        public readonly array $payloadMeta,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        TelemetryLog::create([
            'license_id' => $this->licenseId,
            'request_ip' => $this->requestIp,
            'domain_used' => $this->domainUsed,
            'app_version' => $this->appVersion,
            'result' => $this->result,
            'payload_meta' => $this->payloadMeta,
            'pinged_at' => now(),
        ]);
    }
}
