<?php
namespace App\Listeners;

use App\Events\TicketCreated;
use App\Models\Notification;
use App\Models\User;

class SendNewTicketNotification
{
    public function handle(TicketCreated $event)
    {
        // Notify all admin
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'recipient_id' => $admin->id,
                'recipient_role' => 'admin',
                'sender_id' => $event->creator->id,
                'ticket_id' => $event->ticket->id,
                'title' => 'Tiket Baru',
                'message' => 'Tiket baru telah dibuat: ' . $event->ticket->judul,
                'type' => 'new_ticket',
            ]);
        }
    }
}
