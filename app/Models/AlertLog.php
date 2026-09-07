<?php

namespace App\Models;

use App\Concerns\HasUuidv7;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable([
    'license_id', 'channel', 'type', 'milestone_days',
    'status', 'error_message', 'sent_at',
])]
class AlertLog extends Model
{
    use HasUuidv7;

    #[Override]
    protected function casts()
    {
        return [
            'sent_at' => 'datetime',
            'sent_date' => 'date',
            'milestone_days' => 'integer',
        ];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}
