<?php

namespace App\Enums;

enum LicenseStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Expired = 'expired';
    case UnregisteredDomain = 'unregistered_domain';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Expired => 'Expired',
            self::UnregisteredDomain => 'Unregistered Domain',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Suspended => 'danger',
            self::Expired => 'warning',
            self::UnregisteredDomain => 'gray',
        };
    }
}
