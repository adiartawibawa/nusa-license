<?php

namespace App\Models;

use App\Concerns\HasUuidv7;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable([
    'client_id', 'domain_name', 'server_ip',
    'is_primary', 'is_verified', 'verified_at',
])]
class Domain extends Model
{
    use HasFactory, HasUuidv7;

    #[Override]
    protected function casts()
    {
        return [
            'is_primary' => 'boolean',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    // Normalisasi domain saat disimpan — hindari mismatch "www." atau case
    protected static function booted(): void
    {
        static::saving(function (Domain $domain) {
            $domain->domain_name = strtolower(trim($domain->domain_name));
        });
    }
}
