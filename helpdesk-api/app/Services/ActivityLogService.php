<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Str;

class ActivityLogService
{
    /**
     * Catat aktivitas user ke tabel activity_logs
     *
     * @param string|null $userId
     * @param string $activity
     * @param string|null $description
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @return void
     */
    public static function log($userId, $activity, $description = null, $ipAddress = null, $userAgent = null)
    {
        ActivityLog::create([
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'activity' => $activity,
            'description' => $description,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }
}
