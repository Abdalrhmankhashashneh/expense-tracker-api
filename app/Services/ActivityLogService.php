<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogService
{
    /**
     * Log a user activity.
     */
    public static function log(
        int $userId,
        string $action,
        ?string $module = null,
        ?string $description = null,
        ?Request $request = null
    ): ActivityLog {
        return ActivityLog::create([
            'user_id'    => $userId,
            'action'     => $action,
            'module'     => $module,
            'description' => $description,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? substr($request->userAgent() ?? '', 0, 255) : null,
        ]);
    }
}
