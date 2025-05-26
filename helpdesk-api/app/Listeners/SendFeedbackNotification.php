<?php
namespace App\Listeners;

use App\Events\TicketFeedbackAdded;
use App\Models\Notification;
use App\Models\User;

class SendFeedbackNotification
{
    public function handle(TicketFeedbackAdded $event)
    {
        $ticket = $event->ticket;
        $feedback = $event->feedback;
        $sender = $event->sender;
        // Jika feedback dari admin/disposisi, notifikasi ke student
        if (in_array($sender->role, ['admin', 'disposisi'])) {
            Notification::create([
                'recipient_id' => $ticket->user_id,
                'recipient_role' => 'student',
                'sender_id' => $sender->id,
                'ticket_id' => $ticket->id,
                'title' => 'Feedback Baru',
                'message' => 'Feedback baru untuk tiket: ' . $ticket->judul,
                'type' => 'feedback',
            ]);
        }
        // Jika feedback dari student, notifikasi ke admin dan disposisi
        if ($sender->role === 'student') {
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                Notification::create([
                    'recipient_id' => $admin->id,
                    'recipient_role' => 'admin',
                    'sender_id' => $sender->id,
                    'ticket_id' => $ticket->id,
                    'title' => 'Feedback Baru',
                    'message' => 'Feedback baru untuk tiket: ' . $ticket->judul,
                    'type' => 'feedback',
                ]);
            }
            if ($ticket->assigned_to) {
                Notification::create([
                    'recipient_id' => $ticket->assigned_to,
                    'recipient_role' => 'disposisi',
                    'sender_id' => $sender->id,
                    'ticket_id' => $ticket->id,
                    'title' => 'Feedback Baru',
                    'message' => 'Feedback baru untuk tiket: ' . $ticket->judul,
                    'type' => 'feedback',
                ]);
            }
        }
    }
}
