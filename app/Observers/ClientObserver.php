<?php

namespace App\Observers;

use App\Models\Client;
use Illuminate\Support\Facades\Cache;

class ClientObserver
{
    public function created(Client $client): void
    {
        $this->forgetWidgetCaches();
    }

    public function updated(Client $client): void
    {
        // Cukup invalidate kalau field yang relevan ke widget berubah —
        // hindari cache-bust tiap kali field lain (mis. address) diubah.
        if ($client->wasChanged(['tier', 'is_active'])) {
            $this->forgetWidgetCaches();
        }
    }

    public function deleted(Client $client): void
    {
        $this->forgetWidgetCaches();
    }

    public function restored(Client $client): void
    {
        $this->forgetWidgetCaches();
    }

    private function forgetWidgetCaches(): void
    {
        Cache::forget('widget:client_overview');
        Cache::forget('widget:client_tier_distribution');
    }
}
