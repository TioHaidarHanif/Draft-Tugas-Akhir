<?php
namespace App\Listeners;

use App\Events\TicketStatusChanged;
use App\Models\Notification;
use App\Models\User;

class SendStatusChangeNotification
{
    public function handle(TicketStatusChanged $event)
    {
        $ticket = $event->ticket;
        $changedBy = $event->changedBy;
        // Notifikasi ke student (creator)
        if (in_array($changedBy->role, ['admin', 'disposisi'])) {
            Notification::create([
                'recipient_id' => $ticket->user_id,
                'recipient_role' => 'student',
                'sender_id' => $changedBy->id,
                'ticket_id' => $ticket->id,
                'title' => 'Status Tiket Diperbarui',
                'message' => 'Status tiket telah diperbarui dari ' . $event->oldStatus . ' menjadi ' . $event->newStatus,
                'type' => 'status_change',
            ]);
        }
        // Notifikasi ke admin jika diubah disposisi
        if ($changedBy->role === 'disposisi') {
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                Notification::create([
                    'recipient_id' => $admin->id,
                    'recipient_role' => 'admin',
                    'sender_id' => $changedBy->id,
                    'ticket_id' => $ticket->id,
                    'title' => 'Status Tiket Diperbarui',
                    'message' => 'Status tiket telah diperbarui dari ' . $event->oldStatus . ' menjadi ' . $event->newStatus,
                    'type' => 'status_change',
                ]);
            }
        }
        // Notifikasi ke disposisi jika diubah admin
        if ($changedBy->role === 'admin' && $ticket->assigned_to) {
            Notification::create([
                'recipient_id' => $ticket->assigned_to,
                'recipient_role' => 'disposisi',
                'sender_id' => $changedBy->id,
                'ticket_id' => $ticket->id,
                'title' => 'Status Tiket Diperbarui',
                'message' => 'Status tiket telah diperbarui dari ' . $event->oldStatus . ' menjadi ' . $event->newStatus,
                'type' => 'status_change',
            ]);
        }
    }
}
