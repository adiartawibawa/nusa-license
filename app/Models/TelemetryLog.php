<?php

namespace App\Models;

use App\Concerns\HasUuidv7;
use App\Enums\TelemetryResult;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable([
    'license_id', 'request_ip', 'domain_used',
    'app_version', 'result', 'payload_meta', 'pinged_at',
])]
class TelemetryLog extends Model
{
    use HasFactory, HasUuidv7;

    // Append-only — matikan updated_at agar tidak percuma nulis kolom yang tak pernah berubah
    const UPDATED_AT = null;

    #[Override]
    protected function casts()
    {
        return [
            'result' => TelemetryResult::class,
            'payload_meta' => 'array', // jsonb <-> array otomatis
            'pinged_at' => 'datetime',
        ];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}
