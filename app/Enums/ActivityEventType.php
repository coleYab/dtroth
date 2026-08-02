<?php

namespace App\Enums;

enum ActivityEventType: string
{
    case KeyRotation = 'key_rotation';
    case FailedVerification = 'failed_verification';
    case ScopeChange = 'scope_change';
}
