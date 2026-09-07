<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LicenseVerifyRequest;
use App\Services\License\LicenseVerificationService;
use Illuminate\Http\JsonResponse;

class LicenseVerifyController extends Controller
{
    public function __construct(
        private readonly LicenseVerificationService $verificationService,
    ) {}

    public function __invoke(LicenseVerifyRequest $request): JsonResponse
    {
        $result = $this->verificationService->verify(
            payload: $request->validated(),
            requestIp: $request->ip(),
        );

        // HTTP 200 selalu — status lock/unlock ada di body, bukan status code,
        // supaya client app bisa parse konsisten tanpa exception handling aneh.
        return response()->json($result);
    }
}
