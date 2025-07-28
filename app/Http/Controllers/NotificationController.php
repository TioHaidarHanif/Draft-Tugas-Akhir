<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    /**
     * Get list of notifications for the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
$query = Notification::where('recipient_id', Auth::id())
        ->with(['recipient:id,name', 'sender:id,name']);           
        // Filter by read status
        if ($request->has('read')) {
            $readStatus = filter_var($request->input('read'), FILTER_VALIDATE_BOOLEAN);
            if ($readStatus) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }
        
        // Filter by notification type
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }
        
        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'notifications' => $notifications,
                'pagination' => [
                    'total' => $notifications->total(),
                    'per_page' => $notifications->perPage(),
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                ],
            ]
        ]);
    }

    /**
     * Mark a notification as read.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        
        // Check if the user has permission to mark this notification as read
        if ($notification->recipient_id !== Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to mark this notification as read',
                'code' => 403
            ], 403);
        }
        
        $notification->read_at = now();
        $notification->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read',
            'data' => [
                'id' => $notification->id,
                'read_at' => $notification->read_at
            ]
        ]);
    }

    /**
     * Mark all notifications as read for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead()
    {
        Notification::where('recipient_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Create a notification.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipient_id' => 'sometimes|exists:users,id',
            'recipient_role' => 'sometimes|in:student,admin,disposisi',
            'ticket_id' => 'sometimes|exists:tickets,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|string|in:new_ticket,assignment,status_change,feedback,custom'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 422
            ], 422);
        }
        
        // Ensure at least one of recipient_id or recipient_role is provided
        if (!$request->has('recipient_id') && !$request->has('recipient_role')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Either recipient_id or recipient_role must be provided',
                'code' => 422
            ], 422);
        }
        
        $sender_id = Auth::id();
        
        // If recipient_role is provided, create a notification for each user with that role
        if ($request->has('recipient_role')) {
            $users = User::where('role', $request->input('recipient_role'))->get();
            $notifications = [];
            
            foreach ($users as $user) {
                // Skip creating a notification for the sender
                if ($user->id === $sender_id) {
                    continue;
                }
                
                $notification = Notification::create([
                    'recipient_id' => $user->id,
                    'recipient_role' => $request->input('recipient_role'),
                    'sender_id' => $sender_id,
                    'ticket_id' => $request->input('ticket_id'),
                    'title' => $request->input('title'),
                    'message' => $request->input('message'),
                    'type' => $request->input('type')
                ]);
                
                $notifications[] = $notification;
            }
            
            // Return the first notification created as the response
            $response = !empty($notifications) ? $notifications[0] : null;
        } else {
            // Create a single notification for the specified recipient
            // Get the recipient user's role
            $recipient = User::find($request->input('recipient_id'));
            $recipientRole = $recipient ? $recipient->role : 'student'; // Default to 'student' if user not found
            
            $response = Notification::create([
                'recipient_id' => $request->input('recipient_id'),
                'recipient_role' => $recipientRole,
                'sender_id' => $sender_id,
                'ticket_id' => $request->input('ticket_id'),
                'title' => $request->input('title'),
                'message' => $request->input('message'),
                'type' => $request->input('type')
            ]);
        }
        
        if (!$response) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create notification',
                'code' => 500
            ], 500);
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Notification created successfully',
            'data' => $response
        ], 201);
    }
}