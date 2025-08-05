<?php

namespace App\Http\Controllers;

use App\Models\ChatAttachment;
use App\Models\ChatMessage;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ChatService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    protected $chatService;
    protected $notificationService;

    /**
     * Create a new controller instance.
     *
     * @param ChatService $chatService
     * @param NotificationService $notificationService
     * @return void
     */
    public function __construct(ChatService $chatService, NotificationService $notificationService)
    {
        $this->chatService = $chatService;
        $this->notificationService = $notificationService;
    }

    /**
     * Get all chat messages for a ticket.
     *
     * @param Request $request
     * @param string $ticketId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, string $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);

        // Check authorization
        if (!$this->canAccessTicket($ticket)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Get chat messages with pagination
        $perPage = $request->query('per_page', default: 100);
        $messages = $ticket->chatMessages()
            ->with(['user:id,name,email,role', 'attachments'])
            ->orderBy('created_at', 'asc') // Changed to ASC to match test expectations
            ->paginate($perPage);

        // Mark messages as read by current user
        $this->chatService->markMessagesAsRead($messages->items(), Auth::id());
        
        // Mark related notifications as read
        $this->markRelatedNotificationsAsRead($ticket, Auth::user());

        return response()->json($messages);
    }

    /**
     * Mark related notifications as read when chat messages are viewed
     * 
     * @param Ticket $ticket
     * @param \App\Models\User $user
     * @return void
     */
    private function markRelatedNotificationsAsRead($ticket, $user)
    {
        // Find all unread notifications for this ticket and this user
        Notification::where('recipient_id', $user->id)
            ->where('ticket_id', $ticket->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Store a new chat message.
     *
     * @param Request $request
     * @param string $ticketId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, string $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
         // Prevent storing chat if ticket is closed
        if ($ticket->status === 'closed') {
            return response()->json(['message' => 'Cannot send chat on a closed ticket'], 403);
        }
        

        // Check authorization
        if (!$this->canAccessTicket($ticket)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create chat message
        $chatMessage = new ChatMessage([
            'ticket_id' => $ticketId,
            'user_id' => Auth::id(),
            'message' => $request->message,
            'is_system_message' => false,
            'read_by' => [Auth::id()], // Initially read by sender
        ]);

        $chatMessage->save();

        // Create notification for new chat message
        $this->chatService->createChatMessageNotification($ticket, $chatMessage);

        return response()->json($chatMessage->load(['user:id,name,email,role']), 201);
    }

    /**
     * Delete a chat message.
     *
     * @param string $ticketId
     * @param int $messageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $ticketId, int $messageId)
    {
        $ticket = Ticket::findOrFail($ticketId);
         // Prevent storing chat if ticket is closed
        if ($ticket->status === 'closed') {
            return response()->json(['message' => 'Cannot edit chat on a closed ticket'], 403);
        }
        
        $message = ChatMessage::findOrFail($messageId);

        // Check authorization
        if (!$this->canAccessTicket($ticket)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Only the message author or admin can delete the message
        if ($message->user_id != Auth::id() && Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'You can only delete your own messages'], 403);
        }

        $message->delete();

        return response()->json(['message' => 'Chat message deleted successfully']);
    }

    /**
     * Upload an attachment for a chat message.
     *
     * @param Request $request
     * @param string $ticketId
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadAttachment(Request $request, string $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
 // Prevent storing chat if ticket is closed
        if ($ticket->status === 'closed') {
            return response()->json(['message' => 'Cannot send attachment on a closed ticket'], 403);
        }
        
        // Check authorization
        if (!$this->canAccessTicket($ticket)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'message' => 'nullable|string|max:10000',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240', // 10MB max, only JPG, PNG, PDF
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'File validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create chat message
        $chatMessage = new ChatMessage([
            'ticket_id' => $ticketId,
            'user_id' => Auth::id(),
            'message' => $request->message ?? 'Sent an attachment',
            'is_system_message' => false,
            'read_by' => [Auth::id()], // Initially read by sender
        ]);

        $chatMessage->save();

        // Handle file upload
        $file = $request->file('file');
        $fileName = $file->getClientOriginalName(); // Removed timestamp prefix to match test expectations
        $fileType = $file->getMimeType();
        $fileSize = $file->getSize();
        
        // Store file
        $filePath = $file->storeAs('chat_attachments/' . $ticketId, $fileName, 'public');
        $fileUrl = asset('storage/' . $filePath);

        // Create chat attachment
        $chatAttachment = new ChatAttachment([
            'chat_message_id' => $chatMessage->id,
            'file_name' => $fileName,
            'file_type' => $fileType,
            'file_size' => $fileSize,
            'file_url' => $fileUrl,
        ]);

        $chatAttachment->save();

        // Create notification for new chat message with attachment
        $this->chatService->createChatMessageNotification($ticket, $chatMessage, true);

        return response()->json([
            'message' => $chatMessage->load(['user:id,name,email,role']),
            'attachment' => $chatAttachment
        ], 201);
    }

    /**
     * Get all attachments for a ticket's chat.
     *
     * @param string $ticketId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAttachments(string $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);

        // Check authorization
        if (!$this->canAccessTicket($ticket)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Get chat message IDs for this ticket
        $messageIds = $ticket->chatMessages()->pluck('id');

        // Get attachments for these messages
        $attachments = ChatAttachment::whereIn('chat_message_id', $messageIds)
            ->with('chatMessage:id,user_id,created_at')
            ->get();

        return response()->json($attachments);
    }

    /**
     * Check if the user can access the ticket.
     *
     * @param Ticket $ticket
     * @return bool
     */
    private function canAccessTicket(Ticket $ticket)
    {
        $user = Auth::user();

        return (
            // Admin can access all tickets
            $user->role === 'admin' ||
            // Students can access their own tickets
            ($user->role === 'student' && $ticket->user_id === $user->id)
        );
    }
}