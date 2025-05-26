<?php
namespace App\Listeners;

use App\Events\TicketAssigned;
use App\Models\Notification;

class SendAssignmentNotification
{
    public function handle(TicketAssigned $event)
    {
        Notification::create([
            'recipient_id' => $event->assignedTo->id,
            'recipient_role' => $event->assignedTo->role,
            'sender_id' => $event->assignedBy->id,
            'ticket_id' => $event->ticket->id,
            'title' => 'Tiket Didisposisikan',
            'message' => 'Tiket telah didisposisikan kepada Anda: ' . $event->ticket->judul,
            'type' => 'assignment',
        ]);
    }
}
