<?php

use App\Http\Controllers\Api\V1\LicenseVerifyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/license/verify', LicenseVerifyController::class)
        ->middleware('license.throttle')
        ->name('api.v1.license.verify');
});
