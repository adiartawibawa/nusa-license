<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class LicenseVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // otorisasi via signature, bukan session
    }

    public function rules(): array
    {
        return [
            'license_key' => ['required', 'uuid'],
            'domain' => ['required', 'string', 'max:255'],
            'timestamp' => ['required', 'integer'],
            'nonce' => ['required', 'string', 'size:16'],
            'signature' => ['required', 'string', 'size:64'], // hex sha256
            'app_version' => ['nullable', 'string', 'max:20'],
        ];
    }
}
