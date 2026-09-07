<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasUuidv7
{
    protected static function bootHasUuidv7(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid7();
            }
        });
    }

    public function initializeHasUuidv7(): void
    {
        $this->keyType = 'string';
        $this->incrementing = false;
    }

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    // Opsional: cegah uuidv4 nyasar masuk manual insert tanpa lewat creating event
    public function uniqueIds(): array
    {
        return [$this->getKeyName()];
    }
}
