<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * Log user activity
     *
     * @param string $activity The activity name
     * @param string|null $description Optional description of the activity
     * @param int|string|null $userId Optional user ID (defaults to authenticated user)
     * @return ActivityLog
     */
    public static function log(string $activity, ?string $description = null, $userId = null): ActivityLog
    {
        // If userId is not provided, try to get it from the authenticated user
        if ($userId === null && Auth::check()) {
            $userId = Auth::id();
        }
        
        return ActivityLog::create([
            'user_id' => $userId,
            'activity' => $activity,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
    
    /**
     * Log authentication related activities
     *
     * @param string $activity Authentication activity type
     * @param string|null $description Optional description
     * @param int|string|null $userId Optional user ID
     * @return ActivityLog
     */
    public static function logAuth(string $activity, ?string $description = null, $userId = null): ActivityLog
    {
        return self::log('auth_' . $activity, $description, $userId);
    }
    
    /**
     * Log user profile related activities
     *
     * @param string $activity Profile activity type
     * @param string|null $description Optional description
     * @param int|string|null $userId Optional user ID
     * @return ActivityLog
     */
    public static function logProfile(string $activity, ?string $description = null, $userId = null): ActivityLog
    {
        return self::log('profile_' . $activity, $description, $userId);
    }
    
    /**
     * Log user management related activities (admin actions)
     *
     * @param string $activity User management activity type
     * @param string|null $description Optional description
     * @param int|string|null $userId Optional user ID of the admin
     * @return ActivityLog
     */
    public static function logUserManagement(string $activity, ?string $description = null, $userId = null): ActivityLog
    {
        return self::log('user_management_' . $activity, $description, $userId);
    }
}
