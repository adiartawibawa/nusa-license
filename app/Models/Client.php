<?php

namespace App\Models;

use App\Concerns\HasUuidv7;
use App\Observers\ClientObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

#[Fillable([
    'name', 'company_name', 'email', 'phone_number',
    'pic_name', 'villa_address', 'tier', 'is_active',
])]
#[ObservedBy([ClientObserver::class])]
class Client extends Model
{
    use HasFactory, HasUuidv7, SoftDeletes;

    #[Override]
    protected function casts()
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function activeLicense(): ?License
    {
        return $this->licenses()
            ->where('status', 'active')
            ->latest('issued_at')
            ->first();
    }
}
