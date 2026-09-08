<?php

return [

    /*
    |--------------------------------------------------------------------------
    | License Verification
    |--------------------------------------------------------------------------
    */
    'verification' => [
        // Berapa lama hasil resolveStatus() di-cache sebelum dihitung ulang.
        // Kill-switch manual (forceSuspend/reactivate) bypass ini via Cache::forget().
        'status_cache_ttl' => env('LICENSE_STATUS_CACHE_TTL', 60),

        // Toleransi selisih waktu antara timestamp request client dan server (clock skew).
        'max_timestamp_drift_seconds' => env('LICENSE_MAX_TIMESTAMP_DRIFT', 300),

        // Berapa lama nonce disimpan untuk cegah replay attack.
        'nonce_ttl_seconds' => env('LICENSE_NONCE_TTL', 600),

        // Panjang signing_secret yang digenerate saat create/regenerate.
        'signing_secret_length' => 64,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'throttle' => [
        'max_attempts' => env('LICENSE_THROTTLE_MAX_ATTEMPTS', 30),
        'decay_seconds' => env('LICENSE_THROTTLE_DECAY_SECONDS', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Expiry & Reminder
    |--------------------------------------------------------------------------
    */
    'expiry' => [
        // Milestone H- berapa saja reminder dikirim sebelum expired.
        'reminder_days' => [7, 3, 1],

        // Contoh: 3 hari toleransi setelah expires_at sebelum benar-benar EXPIRED.
        // Set null kalau tidak ingin ada grace period sama sekali.
        'default_grace_period_days' => env('LICENSE_DEFAULT_GRACE_PERIOD_DAYS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Widget Cache
    |--------------------------------------------------------------------------
    */
    'widget_cache_ttl' => [
        'stats' => env('WIDGET_STATS_CACHE_TTL', 55),
        'trend' => env('WIDGET_TREND_CACHE_TTL', 300),
    ],

];
