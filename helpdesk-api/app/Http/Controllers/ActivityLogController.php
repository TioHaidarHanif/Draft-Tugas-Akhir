<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    // GET /activity-logs (admin only)
    public function index(Request $request)
    {
        $logs = ActivityLog::with('user')
            ->orderByDesc('created_at')
            ->paginate(30);

        return response()->json([
            'status' => 'success',
            'data' => $logs
        ]);
    }
}
