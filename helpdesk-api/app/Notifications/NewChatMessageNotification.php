<?php
namespace App\Notifications;

use App\Models\ChatMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewChatMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $message;

    public function __construct(ChatMessage $message)
    {
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'ticket_id' => $this->message->ticket_id,
            'chat_message_id' => $this->message->id,
            'user_id' => $this->message->user_id,
            'message' => $this->message->message,
            'created_at' => $this->message->created_at,
        ];
    }
}
