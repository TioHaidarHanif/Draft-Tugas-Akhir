<?php
namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatAttachment;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewChatMessageNotification;
use Symfony\Component\HttpFoundation\Response;

class ChatController extends Controller
{
    // GET /tickets/{id}/chat
    public function index($ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        $this->authorizeAccess($ticket);
        $messages = ChatMessage::with(['user', 'attachments'])
            ->where('ticket_id', $ticketId)
            ->orderBy('created_at')
            ->get();
        return response()->json(['messages' => $messages]);
    }

    // POST /tickets/{id}/chat
    public function store(Request $request, $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        $this->authorizeAccess($ticket);
        $request->validate([
            'message' => 'required_without:attachment|string|nullable',
        ]);
        $message = ChatMessage::create([
            'ticket_id' => $ticketId,
            'user_id' => Auth::id(),
            'message' => $request->message,
        ]);
        // Notifikasi ke user terkait ticket
        $this->notifyUsers($ticket, $message);
        return response()->json(['message' => $message], Response::HTTP_CREATED);
    }

    // POST /tickets/{id}/chat/attachment
    public function uploadAttachment(Request $request, $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        $this->authorizeAccess($ticket);
        $request->validate([
            'chat_message_id' => 'required|exists:chat_messages,id',
            'file' => 'required|file|max:10240', // 10MB
        ]);
        $file = $request->file('file');
        $path = $file->store('chat_attachments');
        $attachment = ChatAttachment::create([
            'chat_message_id' => $request->chat_message_id,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);
        return response()->json(['attachment' => $attachment], Response::HTTP_CREATED);
    }

    // DELETE /tickets/{id}/chat/{message_id}
    public function destroy($ticketId, $messageId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        $this->authorizeAccess($ticket);
        $message = ChatMessage::findOrFail($messageId);
        if ($message->user_id !== Auth::id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $message->delete();
        return response()->json(['success' => true]);
    }

    // GET /tickets/{id}/chat/attachments
    public function attachments($ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        $this->authorizeAccess($ticket);
        $attachments = ChatAttachment::whereHas('message', function($q) use ($ticketId) {
            $q->where('ticket_id', $ticketId);
        })->get();
        return response()->json(['attachments' => $attachments]);
    }

    // Helper: otorisasi akses chat ticket
    protected function authorizeAccess($ticket)
    {
        $user = Auth::user();
        if ($user->role === 'admin' || $user->id === $ticket->user_id || $user->id === $ticket->assigned_to) {
            return true;
        }
        abort(403, 'Unauthorized');
    }

    // Helper: notifikasi pengguna terkait chat baru
    protected function notifyUsers($ticket, $message)
    {
        // Kirim notifikasi ke pemilik ticket dan assigned user (kecuali pengirim)
        $recipients = collect([$ticket->user, $ticket->assignedTo])->filter(function($u) use ($message) {
            return $u && $u->id !== $message->user_id;
        });
        foreach ($recipients as $user) {
            if ($user) {
                Notification::send($user, new NewChatMessageNotification($message));
            }
        }
    }
}
