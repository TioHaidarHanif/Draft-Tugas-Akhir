<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ChatService
{
    /**
     * Create a notification for a new chat message.
     *
     * @param Ticket $ticket
     * @param ChatMessage $chatMessage
     * @param bool $hasAttachment
     * @return void
     */
    public function createChatMessageNotification(Ticket $ticket, ChatMessage $chatMessage, bool $hasAttachment = false)
    {
        $sender = $chatMessage->user;
        $attachmentText = $hasAttachment ? ' with attachment' : '';
        
        // Determine message and recipients based on sender's role
        if ($sender->role === 'admin') {
            // If sender is admin, notify student and assigned disposisi
            $this->notifyUserAboutChatMessage(
                $ticket->user_id, 
                $ticket, 
                $sender->id, 
                "New chat message{$attachmentText} from admin"
            );
            
            if ($ticket->assigned_to && $ticket->assigned_to !== $sender->id) {
                $this->notifyUserAboutChatMessage(
                    $ticket->assigned_to, 
                    $ticket, 
                    $sender->id, 
                    "New chat message{$attachmentText} from admin"
                );
            }
        } elseif ($sender->role === 'disposisi') {
            // If sender is disposisi, notify student and admin
            $this->notifyUserAboutChatMessage(
                $ticket->user_id, 
                $ticket, 
                $sender->id, 
                "New chat message{$attachmentText} from support"
            );
            
            $this->notifyAdminsAboutChatMessage(
                $ticket, 
                $sender->id, 
                "New chat message{$attachmentText} from disposisi"
            );
        } else {
            // If sender is student, notify admin and assigned disposisi
            $this->notifyAdminsAboutChatMessage(
                $ticket, 
                $sender->id, 
                "New chat message{$attachmentText} from student"
            );
            
            if ($ticket->assigned_to) {
                $this->notifyUserAboutChatMessage(
                    $ticket->assigned_to, 
                    $ticket, 
                    $sender->id, 
                    "New chat message{$attachmentText} from student"
                );
            }
        }
    }

    /**
     * Create a system message in the chat.
     *
     * @param Ticket $ticket
     * @param string $message
     * @return ChatMessage
     */
    public function createSystemMessage(Ticket $ticket, string $message): ChatMessage
    {
        return ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id() ?? 1, // Use authenticated user or default to ID 1 (admin)
            'message' => $message,
            'is_system_message' => true,
            'read_by' => [],
        ]);
    }

    /**
     * Mark messages as read by user.
     *
     * @param array $messages
     * @param int $userId
     * @return void
     */
    public function markMessagesAsRead($messages, $userId)
    {
        foreach ($messages as $message) {
            $readBy = $message->read_by ?? [];
            
            if (!in_array($userId, $readBy)) {
                $readBy[] = $userId;
                $message->read_by = $readBy;
                $message->save();
            }
        }
    }

    /**
     * Notify a specific user about a new chat message.
     *
     * @param int $userId
     * @param Ticket $ticket
     * @param int $senderId
     * @param string $message
     * @return void
     */
    private function notifyUserAboutChatMessage($userId, Ticket $ticket, $senderId, $message)
    {
        if (!$userId || $userId === $senderId) {
            return;
        }
        
        $user = User::find($userId);
        
        if (!$user) {
            return;
        }
        
        Notification::create([
            'recipient_id' => $userId,
            'recipient_role' => $user->role,
            'sender_id' => $senderId,
            'ticket_id' => $ticket->id,
            'title' => 'New Chat Message',
            'message' => $message,
            'type' => 'chat_message'
        ]);
    }

    /**
     * Notify all admin users about a new chat message.
     *
     * @param Ticket $ticket
     * @param int $senderId
     * @param string $message
     * @return void
     */
    private function notifyAdminsAboutChatMessage(Ticket $ticket, $senderId, $message)
    {
        $admins = User::where('role', 'admin')->get();
        
        foreach ($admins as $admin) {
            // Skip notifying the sender
            if ($admin->id === $senderId) {
                continue;
            }
            
            Notification::create([
                'recipient_id' => $admin->id,
                'recipient_role' => 'admin',
                'sender_id' => $senderId,
                'ticket_id' => $ticket->id,
                'title' => 'New Chat Message',
                'message' => $message,
                'type' => 'chat_message'
            ]);
        }
    }
}
