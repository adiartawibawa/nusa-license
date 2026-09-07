<?php

namespace App\Observers;

use App\Models\License;

class LicenseObserver
{
    public function updating(License $license): void
    {
        if ($license->isDirty('status')) {
            $license->last_status_changed_at = now();
        }
    }
}
