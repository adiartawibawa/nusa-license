<?php

namespace App\Enums;

enum TelemetryResult: string
{
    case Success = 'success';
    case RejectedSignature = 'rejected_signature';
    case RejectedDomain = 'rejected_domain';
    case RejectedStatus = 'rejected_status';
}
