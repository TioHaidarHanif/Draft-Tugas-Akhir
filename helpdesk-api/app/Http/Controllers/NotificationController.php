<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNotificationRequest;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class NotificationController extends Controller
{
    // GET /notifications
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Notification::where('recipient_id', $user->id);
        if ($request->has('read')) {
            $read = filter_var($request->query('read'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($read === true) {
                $query->whereNotNull('read_at');
            } elseif ($read === false) {
                $query->whereNull('read_at');
            }
        }
        if ($request->has('type')) {
            $query->where('type', $request->query('type'));
        }
        $notifications = $query->orderByDesc('created_at')->paginate($request->query('per_page', 10));
        return response()->json([
            'status' => 'success',
            'data' => [
                'notifications' => $notifications->items(),
                'pagination' => [
                    'total' => $notifications->total(),
                    'per_page' => $notifications->perPage(),
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                ]
            ]
        ]);
    }

    // PATCH /notifications/{id}/read
    public function markAsRead($id)
    {
        $user = Auth::user();
        $notification = Notification::where('id', $id)
            ->where('recipient_id', $user->id)
            ->firstOrFail();
        $notification->read_at = Carbon::now();
        $notification->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read',
            'data' => [
                'id' => $notification->id,
                'read_at' => $notification->read_at,
            ]
        ]);
    }

    // PATCH /notifications/read-all
    public function markAllAsRead()
    {
        $user = Auth::user();
        Notification::where('recipient_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);
        return response()->json([
            'status' => 'success',
            'message' => 'All notifications marked as read',
        ]);
    }

    // POST /notifications
    public function store(StoreNotificationRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();
        $notification = Notification::create([
            'recipient_id' => $data['recipient_id'] ?? null,
            'recipient_role' => $data['recipientRole'] ?? null,
            'sender_id' => $user->id,
            'ticket_id' => $data['ticket_id'] ?? null,
            'title' => $data['title'],
            'message' => $data['message'],
            'type' => $data['type'],
        ]);
        return response()->json([
            'status' => 'success',
            'message' => 'Notification created successfully',
            'data' => $notification
        ]);
    }
}
