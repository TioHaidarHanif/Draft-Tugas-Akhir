<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of activity logs with optional filtering.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Log that activity logs were accessed
        ActivityLogService::logUserManagement('view_logs', 'Activity logs were accessed');
        
        $query = ActivityLog::with('user:id,name,email,role')
            ->orderBy('created_at', 'desc');
        
        // Apply filters if provided
        if ($request->has('activity')) {
            $query->where('activity', 'like', '%' . $request->activity . '%');
        }
        
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Paginate the results
        $logs = $query->paginate($request->per_page ?? 20);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Activity logs retrieved successfully',
            'data' => $logs
        ]);
    }
    
    /**
     * Display the specified activity log.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $log = ActivityLog::with('user:id,name,email,role')->find($id);
        
        if (!$log) {
            return response()->json([
                'status' => 'error',
                'message' => 'Activity log not found',
                'code' => 404
            ], 404);
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Activity log retrieved successfully',
            'data' => $log
        ]);
    }
    
    /**
     * Get activity log statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        $totalLogs = ActivityLog::count();
        $authLogs = ActivityLog::where('activity', 'like', 'auth_%')->count();
        $userManagementLogs = ActivityLog::where('activity', 'like', 'user_management_%')->count();
        $profileLogs = ActivityLog::where('activity', 'like', 'profile_%')->count();
        
        $logsByDate = ActivityLog::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Activity log statistics retrieved successfully',
            'data' => [
                'total_logs' => $totalLogs,
                'auth_logs' => $authLogs,
                'user_management_logs' => $userManagementLogs,
                'profile_logs' => $profileLogs,
                'logs_by_date' => $logsByDate
            ]
        ]);
    }
}
