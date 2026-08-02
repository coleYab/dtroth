<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogger
{
    /**
     * Record an audit log entry.
     */
    public static function log(User $user, string $action, string $description, ?int $appId = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => $user->id,
            'app_id' => $appId,
            'action' => $action,
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}
