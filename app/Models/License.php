<?php

namespace App\Models;

use App\Concerns\HasUuidv7;
use App\Enums\LicenseStatus;
use App\Observers\LicenseObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Override;

#[ObservedBy([LicenseObserver::class])]
#[Fillable([
    'client_id', 'license_key', 'signing_secret', 'status',
    'suspend_reason', 'issued_at', 'expires_at',
    'grace_period_until', 'last_verified_at',
    'last_status_changed_at', 'changed_by',
])]
#[Hidden([
    'signing_secret', // jangan pernah ikut ter-serialize ke JSON/API response
])]
class License extends Model
{
    use HasFactory, HasUuidv7;

    #[Override]
    protected function casts()
    {
        return [
            'status' => LicenseStatus::class,
            'signing_secret' => 'encrypted',
            'issued_at' => 'date',
            'expires_at' => 'date',
            'grace_period_until' => 'date',
            'last_verified_at' => 'datetime',
            'last_status_changed_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function telemetryLogs(): HasMany
    {
        return $this->hasMany(TelemetryLog::class);
    }

    public function alertLogs(): HasMany
    {
        return $this->hasMany(AlertLog::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast()
            && (! $this->grace_period_until || $this->grace_period_until->isPast());
    }

    // Route model binding via license_key, bukan UUID primary key (biar API tidak expose id internal)
    public function getRouteKeyName(): string
    {
        return 'license_key';
    }

    protected static function booted(): void
    {
        static::creating(function (License $license) {
            $license->license_key = (string) Str::uuid7();
            $license->signing_secret = Str::random(64);
            $license->status ??= LicenseStatus::Active;
        });
    }
}
